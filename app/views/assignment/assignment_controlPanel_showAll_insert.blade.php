

<div class="panel panel-default">

  <div class="panel-heading">
    <h3 class="panel-title">Download results</h3>
  </div>
  <div class="panel-body">
    
    <p>
		Here you can download all student results as csv file.
	</p>
	
	<p>
		<a href="{{{route('getAssignmentResultsAsCsv', $assignment->id)}}}" class="btn btn-primary">Download csv</a>
	</p> 
  
  </div>


  <div class="panel-heading">
    <h3 class="panel-title">Download archive</h3>
  </div>
  <div class="panel-body">
    
		
		<p>
			Here you can download the entire assignment as one big zip archive containing all student answers, reviews, marks, etc.
		</p>
		
		<p>
			This archive is meant to be browsable (with a web browser) and self-sustained. As such it has to contain some assets and 
			libraries. In particular the entire Mathjax library is included which is responsible for about 30MB of compressed data.  
		</p>
		
		@if(!function_exists('gzdeflate'))
			<div class="alert alert-warning" role="alert">
				This server does not seem to have Zlib installed. The zip archive will be large and uncompressed.  
			</div>
		@endif
				
		<p>
		
		
			<a href="{{{ route('assignmentDownloadZip', $assignment->id) }}}" class="btn btn-primary">Download zip archive</a> 
		</p>

  
  </div>



  <div class="panel-heading">
    <h3 class="panel-title">Show all student data</h3>
  </div>
  <div class="panel-body">
    
		
		<p>
					This page shows all student data including all answers, reviews, marks, etc. in a <strong>single page</strong>.
					This page not only takes long to load, it is also likely to exhaust your web browser's memory if your class is large.
		</p>
		
		<p>
			Usually, the better option is to use the <a href="{{{ route('assignmentBrowseStudentList', $assignment->id) }}}">Browse students</a> page
			to access a student's data or to <a href="{{{ route('assignmentDownloadZip', $assignment->id) }}}">download the zip archive</a>. 
		</p>
				
		<?php 
			$confirmationMessage = '
				<p>
					This page shows all student data including all answers, reviews, marks, etc. in a <strong>single page</strong>
				</p><p>
					This page not only takes long to load, it is also likely to exhaust your web browser\'s memory if your class is large.
					Are you sure you want to browse to this page? 
				</p>';
		
		?>
		
		
		<p>
		
		
			<a href="{{{ route('assignmentShowAllStudentsMonster', $assignment->id) }}}"
				data-confirmationdialog="{{ $confirmationMessage }}" 
				data-confirmationbutton="Show all data now">
				<button class="btn btn-default">Show all student data</button>
			</a> 
		</p>

  
  </div>
</div>


