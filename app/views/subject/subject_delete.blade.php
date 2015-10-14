@extends('subject.subject_template') 

@section('title') 
Delete {{{ $subject->code }}}
@stop 


@section('content')
@parent

<div class="row add-bottom-margin">
<div class="col-md-12">
Do you really want to delete this class and all data that is associated with it? This cannot be undone.
</div>
</div>

<div class="row add-bottom-margin">
<div class="col-md-2 text-right"><strong>Code:</strong></div>
<div class="col-md-10">{{{ $subject->code }}}</div>
</div>

<div class="row add-bottom-margin">
<div class="col-md-2 text-right"><strong>Title:</strong></div>
<div class="col-md-10">{{{ $subject->title }}}</div>
</div>

<div class="row add-bottom-margin">
<div class="col-md-2 text-right"><strong>Dates:</strong></div>
<div class="col-md-10">{{{ $subject->start_date }}} - {{{ $subject->end_date }}}</div>
</div>


{{ Form::open_horizontal(array('route' => array('deleteSubject', 'id' => $subject->id))) }}

{{ Form::checkbox_group('doit', 'Yes, I really want to delete this class.', 'now', false, $errors) }}

{{ Form::submit_group(array('submit_title' => 'Delete class now', 'cancel_url' => route('showSubject', $subject->id), 'cancel_title' => 'Cancel')) }}

{{ Form::close() }}


@stop
