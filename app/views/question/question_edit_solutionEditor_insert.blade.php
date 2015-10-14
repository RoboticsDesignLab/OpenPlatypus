
Solution author
@if($assignment->maySetSolutionEditor(Auth::user()))
	
	<br>
	<small>(you can select the tutor who has to write the official solution)</small>
	
	<div class="dropdown">
		<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
			{{{ $question->solution_editor_name }}} <span class="caret"></span>
		</button>
	
		<ul class="dropdown-menu" role="menu">
			<li><?php 
					$attributes = array();
					$attributes['data-url'] = route('editAssignmentQuestionSolutionEditorAjax', array('id' => $assignment->id, 'question_id' => $question->id));
					$attributes['data-_token'] = csrf_token();
					$attributes['data-tutor'] = 0;
					$attributes['class'] = 'ajaxPost';
					echo link_to('#', '---', $attributes );
			?></li>
			
			@foreach($assignment->all_tutors as $tutor)
				<li><?php 
					$attributes = array();
					$attributes['data-url'] = route('editAssignmentQuestionSolutionEditorAjax', array('id' => $assignment->id, 'question_id' => $question->id));
					$attributes['data-_token'] = csrf_token();
					$attributes['data-tutor'] = $tutor->id;
					$attributes['class'] = 'ajaxPost';
					echo link_to('#', $tutor->presenter()->name, $attributes );
				?></li>
			@endforeach		
		</ul>
	</div>
	
@else
	
	<button class="btn btn-default" disabled>
		{{{ $question->solution_editor_name }}}
	</button>
	
@endif


