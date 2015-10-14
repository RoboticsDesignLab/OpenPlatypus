@extends('subject.subject_template')
 
@section('title') 
Add student to {{{ $subject->code }}}
@stop


@section('content')
@parent

<div class="row add-bottom-margin">
<div class="col-md-12">
The user seems to exist already. Do you want to add the following user?
</div>
</div>

<div class="row add-bottom-margin">
<div class="col-md-2 text-right"><strong>Name:</strong></div>
<div class="col-md-10">{{{ $user->name }}}</div>
</div>

<div class="row add-bottom-margin">
<div class="col-md-2 text-right"><strong>Email:</strong></div>
<div class="col-md-10">{{{ $user->email }}}</div>
</div>

<div class="row add-bottom-margin">
<div class="col-md-2 text-right"><strong>Student ID:</strong></div>
<div class="col-md-10">{{{ $user->student_id }}}</div>
</div>

<div class="row add-bottom-margin">
<div class="col-md-2 text-right"><strong>Role:</strong></div>
<div class="col-md-10">{{{ SubjectMember::explainRole($role) }}}</div>
</div>


<div class="row">
<div class="col-md-offset-2 col-md-10">
{{ Form::post_button_primary( array('route' => array('addExistingUserToSubjectConfirm','id' => $subject->id, 'userid' => $user->id, 'role' => $role ) ),'Add '.SubjectMember::presentRole($role)) }}
<a href="{{{ route("createUserAndAddToSubject", $subject->id) }}}"><button type="submit" class="btn btn-default">Cancel</button></a>

</div>
</div>

@stop
