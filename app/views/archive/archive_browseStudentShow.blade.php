@extends('archive.archive_master_template')

@section('title') 
{{{ $user->name }}}
@stop 


@section('content')
@parent


@include('assignment.assignment_browseStudentShow_insert', array('assignment' => $assignment, 'user' => $user))

@stop
