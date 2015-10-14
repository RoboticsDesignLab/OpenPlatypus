<?php
use McCool\LaravelAutoPresenter\BasePresenter;
use McCool\LaravelAutoPresenter\PresenterInterface;
use Carbon\Carbon;
use Platypus\Helpers\PlatypusBool;


class ReviewStatus extends PlatypusEnum {
	const task = 0;
	const completed = 1;
}


class ReviewReviewerRole extends PlatypusEnum {
	const student = 0;
	const tutor = 1;
	const lecturer = 2;
}

class ReviewFlag extends PlatypusEnum {
	const none = 0;
	const attention = 1;
	const plagiarised = 2;
	const excellent = 3;
	const poor = 4;
}

class ReviewRating extends PlatypusEnum {
	const invalid = -1000;
	const verypoor = -100;
	const poor = -50;
	const neutral = 0;
	const good = 50;
	const excellent = 100;
}

class Review extends PlatypusBaseModel {
	
	// The database table used by the model.
	protected $table = 'reviews';
	
	// define the types that are taken as enum. These will automatically be validated against the proper range.
	public static $enumColumns = array (
			'status' => 'ReviewStatus',
			'reviewer_role' => 'ReviewReviewerRole',
			'review_rated' => 'PlatypusBool',
			'review_rating' => 'ReviewRating',
	);
	

	// fields we can fill directly from user input.
	protected $fillable = array ();
	
	// fields we set with default values
	public static $defaultValues = array(
			'status' => ReviewStatus::task,
			'mark' => null,
			'review_rated' => PlatypusBool::false,
			'review_rating' => null,
	);
	
	
	// fields we want converted to Carbon timestamps on the fly.
	public function getDates() {
		return array (
				'created_at',
				'updated_at',
				'time_submitted',
		);
	}
	
	// define the relationships to other models
	public static $relationsData = array (
			'answer' => array (self::BELONGS_TO,'Answer', 'foreignKey' => 'answer_id'),
			'user' => array (self::BELONGS_TO,'User', 'foreignKey' => 'user_id'),
			'text' => array (self::BELONGS_TO, 'TextBlock', 'foreignKey' => 'text_id'),
			'reviewFeedback' => array (self::BELONGS_TO, 'TextBlock', 'foreignKey' => 'review_feedback_id'),
			'reviewGroup' => array (self::BELONGS_TO,'ReviewGroup', 'foreignKey' => 'review_group_id'),
	);	
	
	// pseudo relationhips go here
	public function question() {
		return $this->answer->question();
	}

	public function assignment() {
		return $this->answer->assignment();
	}
	
	public function siblings() {
		return static::whereNotNull('review_group_id')->where('review_group_id', $this->review_group_id)->where('id', '!=', $this->id);
	}

	public function openSiblings() {
		return $this->siblings()->where('status', ReviewStatus::task);
	}
	
	public function relatedStudentReviews() {
		return static::where('answer_id', $this->answer_id)->where('reviewer_role', ReviewReviewerRole::student)->where('id', '<>', $this->id);
	} 
	
	public function relatedTutorReviews() {
		return static::where('answer_id', $this->answer_id)->where('reviewer_role', ReviewReviewerRole::tutor)->where('id', '<>', $this->id);
	} 
	
	// set the validation rules that apply to this model.
	// the rules are set below. This is a workaround for a stupid php limitation.
	public static $rules = array (
		'mark' => 'numeric|min:0|max:100',
		'review_rating' => 'numeric|min:-1000|max:100',
	);
	
	// receives a copy of the current rules for self-validation
	// has to return the new rules.
	// can be overridden in derived models.
	protected function mangleRulesForValidation($rules) {
	
		$newRules = parent::mangleRulesForValidation($rules);
	
		if ($this->isRated()) {
			array_unshift($newRules['review_rating'], 'required');
		};
	
		return $newRules;
	
	}	
	
	// point out that this model uses a presenter to tweak fields whe n showing them in views.
	public static $presenterClass='ReviewPresenter';
	
	
	public function isCompleted() {
		return $this->status == ReviewStatus::completed;
	}
	
	public function isEmpty() {
		if ($this->isCompleted()) return false;
		if (!is_null($this->mark)) return false;
		if (!$this->text->isEmpty()) return false;
		if ($this->hasFlag()) return false;
		return true;
	}
	
	public function isStudentReview() {
		return $this->reviewer_role == ReviewReviewerRole::student;
	} 
	
	public function isTutorReview() {
		return $this->reviewer_role == ReviewReviewerRole::tutor;
	} 
	
	public function isLecturerReview() {
		return $this->reviewer_role == ReviewReviewerRole::lecturer;
	} 
	
	public function isRated() {
		return (bool) $this->review_rated;
	}
	
	public function isHiddenFromReviewee() {
		return $this->isRated() && ($this->resource->review_rating == -1000);
	} 
	
	public function hasFlag() {
		return $this->flag != ReviewFlag::none;
	}
	
	public function getAllowedFlags() {
		if($this->isStudentReview()) {
			return array(ReviewFlag::none, ReviewFlag::plagiarised);
		} else {
			return ReviewFlag::getValues();
		}
	}
	
	// only validates the review itself, NOT the review feedback.
	public function mayViewTextAndMark(User $user) {
		if(!$this->assignment->mayViewAssignment($user)) return false;
		if ($this->mayEditTextAndMark($user)) return true;
		if ($user->id == $this->user_id) return true;
		if ($this->isCompleted()) {
			if ($this->assignment->mayViewAllSubmittedReviews($user)) return true;
			if ( ($this->answer->user_id == $user->id) && $this->assignment->maySeeReceivedReviews($user) ) {
				if ($this->isHiddenFromReviewee()) return false;
				return true;
			}
			if ( $this->assignment->tutorsCanSeeStudentReviews()) {
				if ($this->answer->getReviewQuery($user)->where('reviewer_role',  ReviewReviewerRole::tutor)->exists()) return true;
			}
		}
		
		return false;
	}
	
	public function mayEditTextAndMark(User $user) {
		if(!$this->assignment->mayViewAssignment($user)) return false;
		if ($this->isCompleted()) return false;
		return ($user->id == $this->user_id);
	}
	
	public function maySubmitReview(User $user) {
		return $this->mayEditTextAndMark($user);
	}
	
	public function mayRetractReview(User $user) {
		if (!$this->isCompleted()) return false;
		if ($user->id != $this->user_id) return false;
		
		if ($this->reviewer_role == ReviewReviewerRole::lecturer) return true;
		if ($this->reviewer_role == ReviewReviewerRole::tutor) return true;
		return false;
	}
	
	public function maySeeFlag(User $user) {
		if(!$this->mayViewTextAndMark($user)) return false;
		if($this->user_id == $user->id) return true;
		if($this->assignment->mayViewAllReviewFlags($user)) return true;
		return false;
	}
	
	public function submitSave(&$errors) {
		$errors = array();
		
		if ($this->isCompleted()) return true; // it is already submitted, so we simply do nothing.
		
		if (!isset($this->mark)) {
			$errors[] = "The mark is not set";
			return false;
		}
		
		if($this->reviewer_role == ReviewReviewerRole::student) {
			if ($this->text->isEmpty()) {
				$errors[] = "The review cannot be empty.";
				return false;
			}
		}
		
		$this->status = ReviewStatus::completed;
		$this->time_submitted = Carbon::now();
		
		$this->save();

		if ($this->isCompleted() and isset($this->review_group_id)) {
			$this->openSiblings()->delete();
		}
		
		return true;
		
	}
	
	public function retractSave() {
		if (!$this->isCompleted()) return true; // it is already submitted, so we simply do nothing.
	
		$this->status = ReviewStatus::task;
		$this->time_submitted = null;
		$this->save();
	}
	
	
	
	
	public static function getARandomValue() {
		return mt_rand();
	}
	
	// create a review task. This method is meant to be fast, thus we don't do sanity checks.
	// the controller has to do that properly.
	static function createReviewTask($answer_id, $user_ids, $reviewer_role) {
		$returnArray = true;
		if (!is_array_or_alike($user_ids)) {
			$returnArray = false;
			$user_ids = array($user_ids);
		}
		
		if (count($user_ids) > 1) {
			$group = ReviewGroup::createNewReviewGroups(1)[0];
		} else {
			$group = null;
		}
		
		$result = array();
		
		foreach($user_ids as $user_id) {
			$review = new static();
			$review->answer_id = $answer_id;
			$review->user_id = $user_id;
			$review->reviewer_role = $reviewer_role;
			$review->status = ReviewStatus::task;
			$review->review_group_id = $group;
			$success = $review->save();
			if(!$success) {
				App::abort(500, 'Could not create review request.');
			}
			$result[] = $review;
		}
		
		if($returnArray) {
			return $result;
		} else {
			return $result[0];
		}
	}

	// mass-create review tasks
	// we need to be able to create a lot of review tasks fast using minimal amounts of DB requests.
	static function createReviewTasks($tasks) {
		
		// get the total count and group count
		$count = 0;
		$groupCount = 0;
		foreach($tasks as &$task) {
			
			if (!is_array_or_alike($task['user_id'])) {
				$task['user_id'] = array($task['user_id']);
			}
			
			if (isset($task['other_group_members'])) {
				foreach($task['other_group_members'] as $user_id) {
					$task['user_id'][] = $user_id;
				}
				unset($task['other_group_members']);
			}
			
			if (count($task['user_id']) > 1) {
				$groupCount++;
			}
			
			$count += count($task['user_id']);
		} unset($task);
		
		// get a bunch of empty text blocks we can use.
		$textBlocks = TextBlock::createNewTextblocks($count);
		$nextBlock = 0;
		
		// get a bunch of ReviewGroups
		$reviewGroups = ReviewGroup::createNewReviewGroups($groupCount);
		$nextGroup = 0;
		
		$time = new Carbon();
		
		$nextId = static::max('id') + 1; 
		
		$rows = array();
		foreach($tasks as $task) {
			$review = array();
			$review['answer_id'] = $task['answer_id'];
			$review['reviewer_role'] = $task['reviewer_role'];
			$review['status'] = ReviewStatus::task;

			$review[static::CREATED_AT] = $time;
			$review[static::UPDATED_AT] = $time;				
			
			if (count($task['user_id']) > 1) {
				$review['review_group_id'] = $reviewGroups[$nextGroup];
				$nextGroup++;				
			}else{
				$review['review_group_id'] = NULL;
			}

						
			foreach($task['user_id'] as $user_id) {
				$review['id'] = $nextId;
				$nextId++;
				$review['random'] = self::getARandomValue();
				$review['user_id'] = $user_id;
				$review['text_id'] = $textBlocks[$nextBlock];
				$nextBlock++;
				$rows[] = $review;
			}
			
		}
		
		if (!empty($rows)) {
			DB::table(static::getTableStatic())->insert($rows);
		}
		
		return count($rows);
	}
	
	

	// create the initial database tables
	static function createTable() {
		Schema::create('reviews', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
				
			$table->increments('id');
			
			$table->integer('random')->unsigned();
				
			$table->integer('answer_id')->unsigned();
			$table->foreign('answer_id')->references('id')->on('answers')->onDelete('cascade');
				
			$table->tinyInteger('status')->default(ReviewStatus::task);
				
			$table->timestamp('time_submitted')->nullable()->default(NULL);
				
			$table->integer('user_id')->unsigned();
			$table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
				
			$table->tinyInteger('reviewer_role');

			$table->integer('review_group_id')->unsigned()->nullable();
			$table->foreign('review_group_id')->references('id')->on('review_groups')->onDelete('set null');
				
			
			$table->double('mark')->nullable()->default(NULL);
			
			$table->tinyInteger('flag')->default(ReviewFlag::none);
				
			$table->integer('text_id')->unsigned();
			$table->foreign('text_id')->references('id')->on('text_blocks')->onDelete('restrict');
		
			$table->boolean('review_rated')->default(PlatypusBool::false);
			$table->double('review_rating')->nullable()->default(NULL);
			$table->integer('review_feedback_id')->unsigned()->nullable();
			$table->foreign('review_feedback_id')->references('id')->on('text_blocks')->onDelete('restrict');
				
			$table->index(array (
					'answer_id',
					'status',
			));
			$table->index(array (
					'user_id',
					'status'
			));
			$table->index(array (
					'user_id',
					'random',
					'id',
			));			
			$table->index(array (
					'random',
					'id',
			));
				
		});
	}
	
	
}


// Add code to attach a text block if the assignment is new.
Review::creating(function($review) {
	if (!isset($review->text_id)) {
		$text = new TextBlock;
		$text->save();
		$review->text_id = $text->id;
	}
	
	if(!isset($review->random)) {
		$review->random = Review::getARandomValue();
	}
	
});


	
// A presenter that formats the dates when showing them in a view.
class ReviewPresenter extends PlatypusBasePresenter {

	public static $presentStatus = array(
			ReviewStatus::task => 'pending',
			ReviewStatus::completed => 'completed',
	);
	
	public static $presentReviewerRole = array(
			ReviewReviewerRole::student => 'student',
			ReviewReviewerRole::lecturer => 'lecturer',
			ReviewReviewerRole::tutor => 'tutor',			
	);
	
	public static $presentFlag = array(
			ReviewFlag::none => '',
			ReviewFlag::attention => 'lecturer attention',
			ReviewFlag::plagiarised => 'answer is plagiarised',			
			ReviewFlag::excellent => 'answer is excellent',
			ReviewFlag::poor => 'answer is exceptionally poor',
	);
	

	public function due_date() {
		if (is_null($this->resource->answers_due)) return '';
		return $this->resource->answers_due->format('d/m/Y (H:i:s)');
	}
	
	public function time_submitted() {
		if (is_null($this->resource->time_submitted)) return '';
		return $this->resource->time_submitted->format('d/m/Y (H:i:s)');
	}
	
	public function status() {
		return $this->presentStatus();
	}
			
	public function mark() {
		if (is_null($this->resource->mark)) {
			return null;
		} else {
			return roundPercentage($this->resource->mark);
		}
	}

	static public function review_rating_static($rating) {
		switch($rating) {
			case -1000: return 'invalid (invisible to reviewee)';
			case -100: return 'very poor';
			case -50: return 'poor';
			case 0: return 'average';
			case 50: return 'good';
			case 100: return 'very good';
			default: return "$rating";
		}
	} 
	
	public function review_rating($rating = null) {
		
		if (!isset($rating)) {
			if(!$this->isRated()) return 'unrated';
			$rating = $this->resource->review_rating;
		}
		
		return self::review_rating_static($rating);

	}
	
	public function review_rating_glyph() {
		if(!$this->isRated()) return '';
		
		switch($this->resource->review_rating) {
			case -1000: return '<span class="glyphicon glyphicon-ban-circle"></span>';
			case -100: return '<span class="glyphicon glyphicon-thumbs-down"></span> <span class="glyphicon glyphicon-thumbs-down"></span>';
			case -50: return '<span class="glyphicon glyphicon-thumbs-down"></span>';
			case 0: return '<span class="glyphicon glyphicon-record"></span>';
			case 50: return '<span class="glyphicon glyphicon-thumbs-up"></span>';
			case 100: return '<span class="glyphicon glyphicon-thumbs-up"></span> <span class="glyphicon glyphicon-thumbs-up"></span>';
			default: return $this->resource->review_rating;
		}
		
	}
}



