@extends('templates.master') 

@section('title') 
How would you like to login? 
@stop

@section('content') 

@foreach($authenticators as $authenticator)
<p class="text-center"><a href="{{{ route('loginDomain', $authenticator[0]) }}}"><button type="button" class="btn btn-primary btn-block btn-lg">{{{ $authenticator[2]}}}</button></a></p>
@endforeach


@stop
