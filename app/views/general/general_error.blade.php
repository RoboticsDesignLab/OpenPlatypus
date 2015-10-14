@extends('templates.master') 



@section('title') 
	@if($code == 404)
		Page not found
	@elseif($code == 403)
		Access denied
	@elseif($code == 401)
		Session expired
	@else
		Error
	@endif
@stop

@section('sub_title') 
	HTTP {{{ $code }}}
@stop


@section('content') 

<strong>

	@if($code == 404)
		We are very sorry. The page you requested could not be found.
	@elseif($code == 403)
		We are very sorry. You do not have access to this page.
	@elseif($code == 401)
		Your session seems to have expired.
	@else
		We are very sorry. An error occured while processing your request.
	@endif

</strong>

@stop
