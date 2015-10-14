<?php 
if(!isset($showSubmissionLog)) {
	$showSubmissionLog = false;
}

if($showSubmissionLog) {
	$events = $assignment->submission_events_ordered;
} else {
	$events = $assignment->events_ordered;
}

?>

<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">
    	@if($showSubmissionLog)
    		Submission log
    	@else
    		Event log
    	@endif
    </h3>
  </div>
  
	@if(count($events) == 0)
		<div class="panel-body">
			<strong>No events have been logged.</strong>
		</div>
	@else    
	<div class="largeScrollBox">
		<table class="table table-condensed">
			@foreach($events as $eventModel)
				<?php $event = $eventModel->presenter(); ?>
				<?php
				  $colourClass = "";
				  if($event->resource->level == AssignmentEventLevel::info) $colourClass = "info";
				  if($event->resource->level == AssignmentEventLevel::warning) $colourClass = "warning";
				  if($event->resource->level == AssignmentEventLevel::error) $colourClass = "danger";				  
				?>
				<tr class="{{{ $colourClass }}}">
					<td class="text-nowrap">{{{	$event->date }}}</td>
					<td>{{{	$event->level }}}</td>
					<td>{{{	$event->text }}}</td>
				</tr>
			@endforeach
		</table>
	</div>
	@endif
  
  
</div>

