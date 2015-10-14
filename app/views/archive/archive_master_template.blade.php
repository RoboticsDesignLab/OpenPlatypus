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



{{-- Bootstrap core CSS --}}
<link href="assets/packages/bootstrap/css/bootstrap.min.css" rel="stylesheet">

{{-- Custom styles for this template --}}
<link href="assets/themes/default.css" rel="stylesheet">

<!-- CSS for code highlighting  -->
<link href="assets/packages/highlight/styles/default.css" rel="stylesheet">


</head>

<body>

	<div class="container">

		<h1>
			@yield('title')<small>@yield('sub_title')</small>
		</h1>

		@yield('content')

	</div>
	
	<div class="container container-fluid-xl">
		@yield('content_wide')
	</div>
	<!-- /.container -->
	
	<!-- Javascript libraries (placed at the end of the document so the pages load faster) -->
	<script src="assets/packages/jquery/jquery-1.11.1.min.js"></script>
	<script src="assets/packages/bootstrap/js/bootstrap.min.js"></script>
	{{-- <script src="{{{ asset('packages/blockui/jquery.blockui.js"></script> --}}
	{{-- <script src="{{{ asset('packages/jgrowl/jquery.jgrowl.min.js"></script> --}}
	<script src="assets/packages/stickytableheaders/jquery.stickytableheaders.min.js"></script>
	{{-- <script src="{{{ asset("packages/$ckeditorVersion/ckeditor.js"></script> --}}
	{{-- <script src="{{{ asset("packages/$ckeditorVersion/adapters/jquery.js") }}}"></script> --}}
	{{-- <script src="http://cdn.mathjax.org/mathjax/2.2-latest/MathJax.js?config=TeX-AMS_HTML" id="mathjaxScriptTag"></script> --}}
	<script src="assets/packages/mathjax/MathJax.js?config=TeX-AMS_HTML" id="mathjaxScriptTag"></script>{{-- we need to tag this one because the src needs to be passed to ckeditor later on. --}}
	<script src="assets/packages/highlight/highlight.pack.js"></script>
	
	
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		
		try {
		
			$(".math-tex").each(function( i ) {
				MathJax.Hub.Queue(["Typeset",MathJax.Hub,this]);
			});
			
			$("code").each(function( i ) {
			    hljs.highlightBlock(this);
			});
			
			$("table.stickyHeaders").stickyTableHeaders({fixedOffset: $('#mainNavigationBar')});
			
			$('.makeTooltip[data-toggle="tooltip"]').tooltip();
			
		} finally {
			$('body').removeClass("loading");
		}
		
	});

	

	
	<!--
		@yield('javascript')
	//-->
	</script>

	
</body>
</html>
