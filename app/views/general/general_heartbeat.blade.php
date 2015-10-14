@extends('templates.master') 

@section('title') 
Platypus system heartbeat
@stop


@section('content') 


<h3>Last garbage collection</h3>
<?php $value = ValueStore::getTime(StoredValue::lastGarbageCollection); ?>
@if(isset($value))
	The garbage collection has last run on {{{ $value->toDayDateTimeString() }}}
@else
	Garbage collection has never run.
@endif
<br>(This should run about once a week to free unused space.)

<h3>Last autostart marking check</h3>
<?php $value = ValueStore::getTime(StoredValue::lastAutostartMarking); ?>
@if(isset($value))
	Autostart marking has last been checked on {{{ $value->toDayDateTimeString() }}}
@else
	Autostart marking has never run.
@endif
<br>(This should run every minute or every few minutes.)

<h3>File storage</h3>
The file storage currently holds {{{ formatFileSize(DiskFile::totalFileSize()) }}} in {{{ DiskFile::totalFileCount() }}} files. 


@stop
