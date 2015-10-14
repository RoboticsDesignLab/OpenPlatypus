@extends('templates.master') 

@section('title') 
All classes
@stop


@section('content') 

<?php $subjects = Subject::orderBy('start_date')->get(); ?>

@if(!empty($subjects))
<table class="table table-hover">
	<thead>
		<tr>
			<th>#</th>
			<th>Code</th>
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
@endif


@stop
