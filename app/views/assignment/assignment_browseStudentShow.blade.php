@extends('assignment.assignment_template') 

@section('title') 
{{{ $user->name }}}
@stop 


@section('content')
@parent

@if($assignment->maySetFinalMarks(Auth::user()))
<div class="text-right add-bottom-margin">
	<a href="{{{ route('showReviewTasks', $assignment->id) }}}?user={{{ $user->id }}}"><button class="btn btn-primary">Mark this student now</button></a>
</div>
@endif

@include('assignment.assignment_browseStudentShow_insert', array('assignment' => $assignment, 'user' => $user))

@stop
