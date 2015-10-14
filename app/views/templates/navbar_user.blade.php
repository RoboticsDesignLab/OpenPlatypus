
{{-- <li><a href="{{ route('home') }}">Home</a></li> --}}


<?php
	$visible_subjects = Auth::user()->visible_subjects_ordered;
?>

@if( (count($visible_subjects) != 1) || Auth::user()->mayCreateClass())
<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">Classes <span class="caret"></span></a>
	<ul class="dropdown-menu" role="menu">
    	<?php $showdivider = false; ?>
		@foreach ($visible_subjects as $subject)
			<?php $showdivider = true; ?>       
			<li>{{ link_to_route('showSubject', $subject->presenter()->code, array( 'id' =>	$subject->id), array() ) }}</li>
		@endforeach
	 
		@if( Auth::user()->mayCreateClass() )
			@if( $showdivider )
				<li class="divider"></li>
			@endif
			<li>{{ link_to_route('newSubject', 'Create a new class' ) }}</li> 
		@endif
	</ul>
</li>
@endif

@yield('navbarextras')

@if( Auth::user()->isAdmin() || Auth::user()->isUserManager() )
<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">Administrator <span class="caret"></span></a>
	<ul class="dropdown-menu" role="menu">
		@if( Auth::user()->isUserManager() )
			<li>{{ link_to_route('listUsersForUserManager', 'Manage user accounts' ) }}</li>
		@endif
		@if( Auth::user()->isAdmin() )
			<li>{{ link_to_route('listAllSubjects', 'List all classes' ) }}</li>
			<li>{{ link_to_route('showHeartBeat', 'Show system heartbeat' ) }}</li>
		@endif
	</ul>
</li>
@endif


<li class="divider"></li>
<li><a href="{{ route('logout') }}">Logout</a></li>

@if(Auth::user()->isDebugger())
<li class="divider"></li>
<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">Debug <span class="caret"></span></a>
	<ul class="dropdown-menu" role="menu">
		<li>{{ link_to_route('debugSwitchUser', 'Switch user' ) }}</li>
		<li>{{ link_to_route('phpInfo', 'Show phpinfo()' ) }}</li>
		<li><a href="/phpmyadmin" target="_blank">phpMyAdmin</a></li>
		<li><a href="/goaccess" target="_blank">GoAccess server statistics</a></li>
	</ul>
</li>
@endif

@if(Session::has('debuggerMayReturnToUser'))
	<li class="bg-danger text-danger"><a href="{{ route('debugSwitchReturn') }}">Leave {{{ Auth::user()->presenter()->name }}}</a></li>
@endif