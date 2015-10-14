@extends('subject.subject_template') 

@section('title') 
{{{ $subject->code }}}
@stop 

@section('sub_title') 
{{{ $subject->title }}} ({{{ $subject->start_date }}} - {{{ $subject->end_date }}})
@stop


@section('content')
@parent


@if(!empty($assignments))
<table class="table table-hover">
	<thead>
		<tr>
			<th>#</th>
			<th>Assignment</th>
			<th>Due date</th>
			<th>Status</th>
		</tr>
	</thead>
	<tbody>
	
	
	<?php $counter = 0; ?>
	@foreach ($assignments as $assignment)
		<?php $assignment = $assignment->presenter(); ?>
		<tr class='clickableRow' data-url="{{ route('showAssignment', $assignment->id) }}">
			<td>{{{ ++$counter }}}</td>
			<td><a href="{{ route('showAssignment', $assignment->id) }}">{{{ $assignment->title }}}</a></td>
			<td>{{{ $assignment->answers_due }}}</td>
			<td>{{{ $assignment->status }}}</td>
		</tr>
	@endforeach
	</tbody>
</table>
@endif



@stop
