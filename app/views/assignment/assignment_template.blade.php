@extends('templates.master')
 
@section('title') 
@stop 

@section('sub_title') 
{{{ $assignment->title }}}
{{-- Due {{{ $assignment->answers_due }}} --}}
@stop


@section('navbarextras')

@include('subject.subject_tab_navigation_insert', array('subject' => $assignment->subject->presenter(), 'forMainMenu' => true))

<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">Assignment <span class="caret"></span></a>
	<ul class="dropdown-menu updatable ajaxUpdater ajaxNoBlockUi ajaxNoAnimation updatableAssignmenNavigationBar" role="menu" data-resourceid="assignment_menu" data-url="{{{ route('updateAssignmentNavigationBarAjax', array('assignment_id' => $assignment->id, 'route_name' => Route::current()->getName(), 'for_main_menu' => 1)) }}}">
		@include('assignment.assignment_tab_navigation_insert', array('assignment' => $assignment, 'forMainMenu' => true))
	</ul>
</li>
@stop

@section('content')

<div class="row hidden-xs">
	<div class="col-md-12">
		<div class="hidden-print updatable ajaxUpdater ajaxNoBlockUi updatableAssignmenNavigationBar" data-resourceid="assignment_tab_navigation_bar" data-url="{{{ route('updateAssignmentNavigationBarAjax', array('assignment_id' => $assignment->id, 'route_name' => Route::current()->getName(), 'for_main_menu' => 0)) }}}">
			@include('assignment.assignment_tab_navigation_insert', array('assignment' => $assignment))
		</div>
	</div>
</div>

@stop
