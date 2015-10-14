@extends('assignment.assignment_template')

@section('title') 
Control panel
@stop 

@section('sub_title') 
{{{ $assignment->title }}}
@stop

@section('content')
@parent

<div class="row">

<div class="col-md-12">
	@include('assignment.assignment_controlPanel_generalSettings_insert',array('assignment'=>$assignment))
</div>

<div class="col-md-12">
	<div class="ajaxUpdater ajaxNoBlockUi" data-resourceid="assignment_log_panel" data-url="{{{ route('updateAssignmentLogPanel', $assignment->id) }}}">
		@include('assignment.assignment_controlPanel_log_insert',array('assignment'=>$assignment))
	</div>
</div>

<div class="col-md-6">
	@include('assignment.assignment_controlPanel_showAll_insert',array('assignment'=>$assignment))
</div>


<div class="col-md-6">
	<div class="ajaxFormWrapper">
		@include('assignment.assignment_controlPanel_marking_insert',array('assignment'=>$assignment))
	</div>
</div>


@if($assignment->mayManageAssignment(Auth::user()))
<div class="col-md-6">
	@include('assignment.assignment_controlPanel_delete_insert',array('assignment'=>$assignment))
</div>
@endif

<div class="col-md-12">
	@include('assignment.assignment_controlPanel_flaggedReviews_insert',array('assignment'=>$assignment))
</div>

<div class="col-md-12">
	@include('assignment.assignment_controlPanel_flaggedReviews_insert',array('assignment'=>$assignment, 'showRatings' => true))
</div>


<div class="col-md-12">
	@include('assignment.assignment_controlPanel_log_insert',array('assignment'=>$assignment, 'showSubmissionLog' => true))
</div>



</div>

@stop
