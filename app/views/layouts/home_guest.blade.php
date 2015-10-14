@extends('templates.master') 

@section('title') 
Welcome to Platypus
@stop


@section('content') 

<div class="add-large-bottom-margin"></div>

<p class="text-center"><a href="{{{ route('login') }}}"><button type="button" class="btn btn-primary btn-block btn-lg">Login</button></a></p>

@stop
