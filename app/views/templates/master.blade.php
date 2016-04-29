<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="">
<meta name="author" content="">
<link href="{{{ asset('favicon.ico') }}}" type="image/x-icon" rel="icon">
<link href="{{{ asset('favicon.ico') }}}" type="image/x-icon"
	rel="shortcut icon">

<title>@yield('title') - Platypus</title>

<?php $ckeditorVersion; ?>
@include('templates.ckeditor_version', array('ckeditorVersionSetter' => function($v) use(&$ckeditorVersion){$ckeditorVersion=$v;}))
<?php $cacheTag = 9; ?>

{{-- Bootstrap core CSS --}}
<link href="{{{ asset('packages/bootstrap/css/bootstrap.min.css') }}}" rel="stylesheet">

{{-- CSS for jGrowl --}}
<link href="{{{ asset('packages/jgrowl/jquery.jgrowl.min.css') }}}"	rel="stylesheet">

{{-- Styles for vertical tabs in bootstrap --}}
<link href="{{{ asset('packages/bootstrap-vertical-tabs/bootstrap.vertical-tabs.min.css') }}}"	rel="stylesheet">

{{-- Styles for xl screens in bootstrap --}}
<link href="{{{ asset('themes/xl-columns.css') }}}"	rel="stylesheet">

{{-- Custom styles for this template --}}
<link href="{{{ asset("themes/default.css?cachetag=$cacheTag") }}}" rel="stylesheet">



{{-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries --}}
<!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

<!-- CSS for code highlighting  -->
<link href="{{{ asset("packages/$ckeditorVersion/plugins/codesnippet/lib/highlight/styles/default.css") }}}" rel="stylesheet">

<style>
body.loading .ajaxFormWrapper a, body.loading .ajaxFormWrapper form {
    visibility: hidden !important;
}
</style>

</head>

<body class="loading">
	<div class="navbar navbar-inverse navbar-platypus navbar-fixed-top" role="navigation" id="mainNavigationBar">
		<div class="pull-right" style="height:0px;">
			<img class="platypusLogo" src="{{{ asset('images/platypus-logo.png') }}}" data-minresizeheight="43" data-baseresizeheight="130" data-aspect="2.46153846153846153846">
		</div>
		<div class="container">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse"
					data-target=".navbar-collapse">
					<span class="sr-only">Toggle navigation</span> <span
						class="icon-bar"></span> <span class="icon-bar"></span> <span
						class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="{{ route('home') }}">Platypus<sup>2</sup></a>
			</div>
			<div class="collapse navbar-collapse">
				<ul class="nav navbar-nav">
					@section('navbarbuttons') 
						@if ((!isset($doNotUseDatabase) && Auth::check()) )
							@include('templates.navbar_user') 
						@else
							@include('templates.navbar_guest') 
						@endif 
					@show
				</ul>
			</div>
			<!--/.nav-collapse -->
		</div>
	</div>

	<div class="container">

		<div class="hidden-print hidden-xs">
			{{ Breadcrumbs::renderIfExists() }}
		</div>


		<h1>
			@yield('title')<small>@yield('sub_title')</small>
		</h1>

		@if(Session::has('danger')) 
			{{ Alert::danger(Session::get('danger') ) }} 
		@endif 
		
		@if(Session::has('warning')) 
			{{Alert::warning(Session::get('warning') ) }} 
		@endif

		@if(Session::has('success')) 
			{{ Alert::success(Session::get('success') ) }} 
		@endif 
		
		@yield('content')

		{{-- <pre>{{ var_dump(DB::getQueryLog()) }}</pre> --}}
		
	</div>
	
	<div class="container container-fluid-xl">
		@yield('content_wide')
	</div>
	<!-- /.container -->

	
	<div class="modal" id="blueprint-largeModal" tabindex="-1" role="dialog">
  		<div class="modal-dialog modal-lg">
    		<div class="modal-content marker-modal-content">
		    </div>
  		</div>
	</div>	
	
	<div class="modal" id="blueprint-alertModal" tabindex="-1" role="dialog">
  		<div class="modal-dialog">
    		<div class="modal-content">
				<div class="modal-header">
        			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        			<h4 class="modal-title">&nbsp;</h4>
      			</div>
      			<div class="modal-body marker-modal-content">
      			</div>
      			<div class="modal-footer">
        			<button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
      			</div>    		
		    </div>
  		</div>
	</div>	
	
	<div class="modal" id="blueprint-confirmationModal" tabindex="-1" role="dialog">
  		<div class="modal-dialog">
    		<div class="modal-content">
				<div class="modal-header">
        			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        			<h4 class="modal-title">Confirmation</h4>
      			</div>
      			<div class="modal-body marker-modal-content">
      			</div>
      			<div class="modal-footer">
      				  <div class="checkbox text-left confirmationCheckboxContainer">
    					<label>
      						<input type="checkbox" class="confirmationCheckbox"><span class="checkboxLabel">I confirm.</span>
    					</label>
  					</div>
        			<button type="button" class="btn btn-primary confirmationButton" data-dismiss="modal">Yes</button>
        			<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        		</div>    		
		    </div>
  		</div>
	</div>		
	
	<!-- Javascript libraries (placed at the end of the document so the pages load faster) -->
	<script src="{{{ asset('packages/jquery/jquery-1.11.1.min.js') }}}"></script>
	<script src="{{{ asset('packages/bootstrap/js/bootstrap.min.js') }}}"></script>
	<script src="{{{ asset('packages/blockui/jquery.blockui.js') }}}"></script>
	<script src="{{{ asset('packages/jgrowl/jquery.jgrowl.min.js') }}}"></script>
	<script src="{{{ asset('packages/stickytableheaders/jquery.stickytableheaders.min.js') }}}"></script>
	<script src="{{{ asset("packages/$ckeditorVersion/ckeditor.js") }}}"></script>
	<script src="{{{ asset("packages/$ckeditorVersion/adapters/jquery.js") }}}"></script>
	{{-- <script src="http://cdn.mathjax.org/mathjax/2.2-latest/MathJax.js?config=TeX-AMS_HTML" id="mathjaxScriptTag"></script> --}}
	<script src="{{{ asset('packages/mathjax/MathJax.js?config=TeX-AMS_HTML,local/local&cachetag='.$cacheTag) }}}" id="mathjaxScriptTag"></script>{{-- we need to tag this one because the src needs to be passed to ckeditor later on. --}}
	<script src="{{{ asset("packages/$ckeditorVersion/plugins/codesnippet/lib/highlight/highlight.pack.js") }}}"></script>
	<script src="{{{ asset("js/platypus.js?cachetag=$cacheTag") }}}"></script>
	
	
	<script type="text/javascript">
	<!--
		@yield('javascript')
	//-->
	</script>

	{{-- IE10 viewport hack for Surface/desktop Windows 8 bug --}}
	<script	src="{{{ asset('packages/bootstrap_assets/js/ie10-viewport-bug-workaround.js') }}}" async></script>
	
</body>
</html>
