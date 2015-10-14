@extends('subject.subject_template')
 
@section('title') 
Upload student list for {{{ $subject->code }}}
@stop 


@section('content')
@parent

<?php $csvdata = Session::has('csvdata') ? Session::get('csvdata') : ""; ?>

<?php 

$csvget = function($key) use ($csvdata) {
	return isset($csvdata[$key]) ? $csvdata[$key] : "";
}

?>

@if(!isset($csvdata['success']))

<div class="panel panel-default"><div class="panel-heading">File upload</div><div class="panel-body">

<div class="row add-bottom-margin"><div class="col-md-12">
	Here you can upload a list of students as CSV file. The file has to have columns for the first and last name, the email address and student ID. 
	You will be able to review all changes before they get applied.
	Please note that this function can be quite slow if a lot of new user accounts need to be created.  
</div></div>


{{ Form::open_horizontal(array('route' => array('massUploadStudentsToSubject', $subject->id), 'files' => true )) }}

{{ Form::file_group('csvfile', 'CSV file', $errors) }}

{{ Form::submit_group(array('submit_title' => 'Upload now')) }}

{{ Form::close() }}

</div></div>

@if(isset($csvdata['raw']))

	<div class="panel panel-default"><div class="panel-heading">Select CSV format</div><div class="panel-body">
	
	{{ Form::open_horizontal(array('route' => array('massUploadStudentsToSubject', $subject->id) )) }}
	
	{{ Form::hidden('raw', $csvget('raw')) }}

	{{ Form::input_group('text', 'delimiter', 'Delimiter', $csvget('delimiter'), $errors, array(), 'The delimiter for the CSV file.') }}
			
	{{ Form::input_group('text', 'quotation', 'Quotation mark', $csvget('quotation'), $errors, array(), 'The quotation mark used in the csv file.') }}		
		
	{{ Form::input_group('text', 'offset', 'Row offset', $csvget('offset'), $errors, array(), 
		'The number of rows in the beginning of the file that are to be ignored. The first non-ignored row is considered to be a header line.') }}		
		
	{{ Form::submit_group(array('submit_title' => 'Update')) }}
	
	{{ Form::close() }}
	
	</div></div>
	
@endif


@if(isset($csvdata['header']))
	
	<div class="panel panel-default"><div class="panel-heading">Select columns to use for fields</div><div class="panel-body">
	
	{{ Form::open_horizontal(array('route' => array('massUploadStudentsToSubject', $subject->id) )) }}
	
	{{ Form::hidden('raw', $csvget('raw')) }}
	{{ Form::hidden('delimiter', $csvget('delimiter')) }}
	{{ Form::hidden('quotation', $csvget('quotation')) }}		
	{{ Form::hidden('offset', $csvget('offset')) }}
	
	@foreach(array('first_name' => 'First name', 'last_name' => 'Last name', 'student_id' => 'Student ID', 'email' => 'Email') as $key => $title)
		<?php $field = "column_$key"; ?>
		{{ Form::select_group($field, $title, $csvget('header'), $csvget($field), $errors) }}
	@endforeach
		
	{{ Form::submit_group(array('submit_title' => 'Update')) }}
	
	{{ Form::close() }}	
	
	</div></div>
	
@endif

@endif

@if(isset($csvdata['students']))
	
	<div class="panel panel-default">
	@if(isset($csvdata['success']))
		<div class="panel-heading">These are the results of your upload.</div>
	@else
		@if(isset($csvdata['ok']))
			<div class="panel-heading">
				Double check that everything is looking good. Then press the confirmation button.
			</div>

			<div class="panel-body">	
				{{ Form::open_horizontal(array('route' => array('massUploadStudentsToSubject', $subject->id) )) }}
	
					{{ Form::hidden('raw', $csvget('raw')) }}
					{{ Form::hidden('delimiter', $csvget('delimiter')) }}
					{{ Form::hidden('quotation', $csvget('quotation')) }}		
					{{ Form::hidden('offset', $csvget('offset')) }}
	
					@foreach(array('first_name', 'last_name', 'student_id', 'email') as $key)
						<?php $field = "column_$key"; ?>
						{{ Form::hidden($field, $csvget($field)) }}
					@endforeach
		
    				{{ Form::hidden('doit', 'now') }}		
		
					{{ Form::submit_group(array('submit_title' => 'Add users to class now!')) }}

				{{ Form::close() }}	
			</div>
	
		@else
			<div class="panel-heading">Please check the errors that occured.</div>
		@endif
	@endif	
	<div class="panel-body">
	
	
	
	
	<table class="table table-hover">
	<thead>
		<tr>
			<th>First name</th>
			<th>Last name</th>
			<th>Student ID</th>
			<th>Email</th>
			<th>Status</th>
		</tr>
	</thead>
	<tbody>
	
	
	@foreach($csvget('students') as $student)
		<tr>
			@foreach(array('first_name', 'last_name', 'student_id', 'email') as $key)
				@if(isset($student["original_$key"]))
					<td class="warning">{{{ $student[$key] }}}<br><span class="text-danger small">(was: {{{ $student["original_$key"] }}})</span></a></td>
				@else
					<td>{{{ $student[$key] }}}</a></td>
				@endif				
			@endforeach
			
			@if(isset($student['error']))
				<td class="danger">{{{ $student['error'] }}}</td>
			@else
				<td>{{{ isset($student['status']) ? $student['status'] : "" }}}</td>
			@endif
		</tr>
	@endforeach
	</tbody>
	</table>	
		


	
	</div></div>
	

@endif

@stop
