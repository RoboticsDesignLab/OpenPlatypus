<?php



class SetupController extends BaseController {

	public function setup() {
		if ($this->databaseIsEmpty()) {
			return View::make('layouts.setup', array (
					'doNotUseDatabase' => true 
			));
		} else {
			return Redirect::route('home')->with('danger', 'The database is not empty. The database can only be initialised on an empty database schema.');
		}
	}
	
	// a convenient way to crop the database tables
	// TODO: remove this!!!!!!!!!!
	public function drop() {
		$this->dropDatabaseTables();
		return Redirect::route('setup')->with('success', 'All database tables cleared.');
		;
	}

	public function setupPost() {
		$onlyValues = array (
				'first_name',
				'last_name',
				'email',
				'password',
				'password_confirmation' 
		);
		$input = Input::only($onlyValues);
		

		$adminUser = new User($input);
		
		$adminUser->authentication_domain = AuthenticationDomain::local;
		

		if (! $adminUser->validate()) {
			
			if (($key = array_search('password', $onlyValues)) !== false) {
				unset($onlyValues [$key]);
			}
			if (($key = array_search('password_confirmation', $onlyValues)) !== false) {
				unset($onlyValues [$key]);
			}
			Input::flashOnly($onlyValues);
			return Redirect::route('setup')->withErrors($adminUser->validationErrors);
		} else {
			
			DB::transaction(function () use($adminUser) {
				$this->createDatabase();
				
				// Create the initial user account
				$adminUser->save();
				
				// Give the initial user admin rights.
				$adminPermission = new Permission(PermissionType::admin, PermissionValue::granted);
				$adminUser->permissions()->save($adminPermission);
			});
			
			Auth::logout();
			return Redirect::route('home')->with('success', 'Database schema created.');
		}
	}

	static function databaseIsEmpty() {
        // DB::select() always returns an array. An empty array is false in PHP.
		return (!DB::select('show tables;'));
	}

	static private function dropDatabaseTables() {
		Schema::dropIfExists('student_group_selection_tokens');
		Schema::dropIfExists('student_group_suggestion_memberships');
		Schema::dropIfExists('student_group_suggestions');
		Schema::dropIfExists('student_group_memberships');
		Schema::dropIfExists('student_groups');
		Schema::dropIfExists('subject_marks');
		Schema::dropIfExists('assignment_marks');
		Schema::dropIfExists('question_marks');
		Schema::dropIfExists('reviews');
		Schema::dropIfExists('autosave_answers');
		Schema::dropIfExists('answer_attachments');
		Schema::dropIfExists('answers');
		Schema::dropIfExists('assignment_questions');
		Schema::dropIfExists('subquestions');
		Schema::dropIfExists('question_revisions');
		Schema::dropIfExists('question_attachments');
		Schema::dropIfExists('questions');
		Schema::dropIfExists('assignment_tutors');
		Schema::dropIfExists('assignment_attachments');
		Schema::dropIfExists('assignments');
		Schema::dropIfExists('subject_members');
		Schema::dropIfExists('subjects');
		Schema::dropIfExists('permissions');
		Schema::dropIfExists('text_block_attachments');
		Schema::dropIfExists('files');
		Schema::dropIfExists('autosave_texts');
		Schema::dropIfExists('text_blocks');
		Schema::dropIfExists('text_block_restrictions');
		Schema::dropIfExists('users');
		Schema::dropIfExists('sessions');
		Schema::dropIfExists('migrations');
	}

	static private function createDatabase() {
		// This entire setup function has to go somewhere else at some stage. I just put the code here so it is there and we can use the database.
		// TODO: move the code somewhere else.
		// The creation of the tables should go into the respective model subjects as static functions Possibly even into the respective models as static functions.

		Schema::create('sessions', function ($t) {
			$t->string('id')->unique();
			$t->longText('payload');
			$t->integer('last_activity');
		});		
		
		ValueStore::createTable();
		
		User::createTable();
		
		TextBlockRestriction::createTable();
		TextBlock::createTable();
		AutosaveText::createTable();
		
		DiskFile::createTable();
		TextBlockAttachment::createTable();
		Permission::createTable();
		Subject::createTable();
		SubjectMember::createTable();
		Assignment::createTable();
		AssignmentEvent::createTable();
		Question::createTable();
		AssignmentTutor::createTable();
		Answer::createTable();
		ReviewGroup::createTable();
		Review::createTable();
		
		QuestionMark::createTable();
		AssignmentMark::createTable();



		StudentGroup::createTable();
		
		StudentGroupSuggestion::createTable();

	}
}
