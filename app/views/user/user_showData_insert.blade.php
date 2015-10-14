<?php

if(!isset($title)) {
	$title = 'Name';
}

?>

<?php $user = $user->presenter(); ?>

<div class="well">
	<dl class="dl-horizontal">
		<dt>{{{ $title }}}: </dt><dd>{{{ $user->name }}}</dd>
  		<dt>Email: </dt><dd>{{{ $user->email }}}</dd>
  		@if(isset($user->resource->student_id))
  			<dt>Student ID: </dt><dd>{{{ $user->student_id }}}</dd>
  		@endif
	</dl>
</div>
