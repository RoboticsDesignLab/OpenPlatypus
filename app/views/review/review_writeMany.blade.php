@extends('assignment.assignment_template') 

@section('title') 
Reviews
@stop 

@section('sub_title') 
	@if($assignment->isStudent(Auth::user()))
		Due on {{{ $assignment->peers_due }}}
	@elseif($assignment->isTutor(Auth::user()))
		Due on {{{ $assignment->tutors_due }}}
	@else
		{{{ $assignment->title }}}
	@endif
@stop


@section($options['useWide'] ? 'content_wide' : 'content')
@parent

@if(isset($options['urlQueryData']) && !isset($options['urlQueryData']['user']))
	<div class="col-cm-12">
		<?php 
			$queryData = $options['urlQueryData'];
	
			$getValue = function($name) use ($queryData) {
				return isset($queryData[$name]) ? $queryData[$name] : null;
			};
			
			
			$makeToggleLink = function($key, $caption, $onCaption = null, $offEnforces = null, $onEnforces = null) use ($queryData,$getValue) {
				if(!isset($onCaption)) $onCaption = $caption;

				$result = '';
				
				$isOn = $getValue($key) == 1;
				
				$data = $queryData;
				$data = array_merge($queryData, array($key => $isOn ? 0 : 1));
				if($isOn and isset($offEnforces)) {
					$data = array_merge($data, $offEnforces);
				}
				if(!$isOn and isset($onEnforces)) {
					$data = array_merge($data, $onEnforces);
				}
				
				$result .= '<a href="' . Request::url().'?'.http_build_query($data) . '" class="btn '. ($isOn ? 'btn-success' : 'btn-default') .'">';
				$result .= $isOn ? $onCaption : $caption;
				$result .= '</a>';
				
				return $result;
			}
		?>
	
		{{ $makeToggleLink('completed','Also show completed tasks'); }}
		
		@if($assignment->maySetFinalMarks(Auth::user()))
			{{ $makeToggleLink('reviewsonly','Only show explicit review tasks') }}
		@endif
		
		{{--
		@if($assignment->isLecturer(Auth::user()) || $assignment->isTutor(Auth::user()))
			{{ $makeToggleLink('automatic','Advance automatically', null, null, array('completed'=>0)) }}
		@endif
		--}}

		@if($assignment->keepAssignmentTogetherWhenShuffling())
			{{ $makeToggleLink('sortbyquestion','Group by question') }}
		@endif
		
		@if(isset($queryData['onlyquestion']))
			<?php 
				$data = array_merge($queryData, array('onlyquestion' => null));
				$question = Question::find($queryData['onlyquestion']);
				if(isset($question)) {
					$question = $question->presenter()->full_position;
				} else {
					$question = '?';
				} 
			?>
			<a href="{{{ Request::url().'?'.http_build_query($data) }}}" class="btn btn-success">
				Only show question {{{ $question }}}
			</a>
		@else
			<div class="dropdown inline">
				<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
					Only show one question <span class="caret"></span>
				</button>
	
				<ul class="dropdown-menu">
					@foreach($assignment->questions_ordered as $question)
						<li><a href="{{{ Request::url().'?'.http_build_query(array_merge($queryData, array('onlyquestion' => $question->id))) }}}">
							Only show question {{{ $question->presenter()->full_position }}}
						</a></li>
					@endforeach
				</ul>
			</div>
		
		@endif


		@if($assignment->isStudent(Auth::user()) && $assignment->usesGroupMarkMode())
			<div class="dropdown inline">
				<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
					@if(!isset($queryData['viewgroup']) || $queryData['viewgroup'] == 0)
						Only show suggested marking <span class="caret"></span>
					@else
						Show marking for whole group <span class="caret"></span>
					@endif
				</button>

				<ul class="dropdown-menu">
					<li><a href="{{{ Request::url().'?'.http_build_query(array_merge($queryData, array('viewgroup' => 0))) }}}">
						Only show suggested marking
					</a></li>
					<li><a href="{{{ Request::url().'?'.http_build_query(array_merge($queryData, array('viewgroup' => 1))) }}}">
						Show marking for whole group
					</a></li>
				</ul>
			</div>
		@endif

	</div>
@endif

<div class="updatable" data-resourceid="paginationLinks">
	{{ $reviews->links() }}
</div>
		
<div class="ajaxFormWrapper" id="panel_groups_order_container">
		
	@foreach($reviewData as $data)
	
		@include('review.review_writeGroup_insert', array(
			'reviews' => $data['reviews'],
			'answers' => $data['answers'],
			'options' => $options, 
		))
		
	@endforeach
	
</div>

<div class="updatable" data-resourceid="paginationLinks">
	{{ $reviews->links() }}
</div>

@if($reviews->getLastPage() > 1)
	{{ makePerPageLinks($reviews, array(1,2,5,10)) }}
@endif

@stop
