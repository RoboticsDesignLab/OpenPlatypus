
<div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" href="#editAssignmentData">
          General settings
        </a>
      </h4>
    </div>
    <div id="editAssignmentData" class="panel-collapse collapse in">
      <div class="panel-body">
      	<div class="well">
      		Because every class has different requirements, there are a many settings for running an assignment. Most of them were
      		implemented upon requests by users. To give you a better understanding of the meaning of the individual settings,
      		there is a <a href="{{{ route('showHelpPage','assignmentsettings') }}}" class="noAjax" target="_blank">help page with further information</a>. 
      	</div>
		<div class="ajaxFormWrapper">
			@if( (isset($showEditForm) && $showEditForm)  || (Session::has('showEditForm') && Session::get('showEditForm')) )
				@include('...assignment.assignment_edit_form_insert', array('assignment'=>$assignment))
			@else 
				@include('...assignment.assignment_edit_show_insert', array('assignment'=>$assignment))
			@endif 
		</div>
	  </div>
    </div>
</div>

