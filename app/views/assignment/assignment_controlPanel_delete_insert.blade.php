

<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">Delete Assignment</h3>
  </div>
  <div class="panel-body">
    
				
		<?php 
			$confirmationMessage = '
				Do you really want to delete the assignment? This will delete:
				<ul>
					<li>The Assignment questions and solutions.</li>
					<li>All written reviews.</li>
					<li>All marks that have been given for this assignment.</li>
				</ul>'; 
		
		?>
		<p>{{ Form::post_button(
			array('route' => array('deleteAssignmentPost', 'id' => $assignment->id)), 
			'Delete assignment', 
			array(
				'data-confirmationdialog' => $confirmationMessage, 
				'data-confirmationbutton' => 'Delete assignment now', 
				'data-confirmationcheckbox' => 'Yes, I really want to delete this assignment now.')
			) }}
		</p>

  
  </div>
</div>


