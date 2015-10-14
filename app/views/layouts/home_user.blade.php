@extends('templates.master') 

@section('title') 
Welcome to Platypus
@stop


@section('content') 





<?php $subjects = Auth::user()->visible_subjects_ordered; ?>

@if(count($subjects) > 0)
	
	@if( Auth::user()->mayCreateClass() )
		<div class="row"><div class="col-md-12 text-right">
			<a href="{{ route('newSubject') }}" class="btn btn-primary">Create a new class</a>
		</div></div>
	@endif
	
	
<table class="table table-hover">
	<thead>
		<tr>
			<th>#</th>
			<th>Class</th>
			<th>Title</th>
		</tr>
	</thead>
	<tbody>
	
	
	<?php $counter = 0; ?>
	@foreach ($subjects as $subject)
		<?php $subject = $subject->presenter(); ?>
		<tr class='clickableRow' data-url="{{ route('showSubject', $subject->id) }}">
			<td>{{{ ++$counter }}}</td>
			<td><a href="{{ route('showSubject', $subject->id) }}">{{{ $subject->code }}}</a></td>
			<td>{{{ $subject->title }}}</td>
		</tr>
	@endforeach
	</tbody>
</table>

@else
	@if(Auth::user()->mayCreateClass() )
		<a href="{{ route('newSubject') }}" class="btn btn-primary btn-block">Create a new class</a>
	@else
		<strong>You don't seem to be enrolled into any classes. If this is a mistake, please contact your lecturer to correct this.</strong>
	@endif
@endif


@stop
