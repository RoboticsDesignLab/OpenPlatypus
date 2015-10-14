
@if(!empty($tabs))

<?php 

if(!isset($currentRoute)) {
	$currentRoute = Route::current()->getName();
}

$isActive = function($tab) use ($currentRoute) {
	if(isset($tab['dropdown'])) {
		foreach ($tab['dropdown'] as $subtab) {
			if($subtab['route'][0] == $currentRoute) return true;
			if (isset($subtab['alternateRoutes']) && (array_search($currentRoute, $subtab['alternateRoutes'])!==false)) return true;
		}		
	} else {
		if($tab['route'][0] == $currentRoute) return true;
		if (isset($tab['alternateRoutes']) && (array_search($currentRoute, $tab['alternateRoutes'])!==false)) return true;
	}
	return false;	
};


if(!isset($forMainMenu)) {
	$forMainMenu = false;
}


?>

@if(!$forMainMenu)
<ul class="nav nav-pills nav-justified add-bottom-margin hidden-print">
@endif
	@foreach ($tabs as $tab)
		@if(isset($tab['dropdown']))
			@if(!$forMainMenu)
			<li class="dropdown{{ $isActive($tab) ? ' active' : '' }}">
				<a class="dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-expanded="false">{{{ $tab['title'] }}} <span class="caret"></span></a>
				<ul class="dropdown-menu" role="menu">
			@endif
				  	@foreach ($tab['dropdown'] as $subtab)
						<li{{ (!$forMainMenu && $isActive($subtab)) ? ' class="active"' : '' }}><a href="{{{ route($subtab['route'][0], $subtab['route'][1]) }}}">{{{ $subtab['title'] }}}</a></li>
				   	@endforeach
			@if(!$forMainMenu)
    			</ul>
			</li>
			@endif
		@else
			<li{{ (!$forMainMenu && $isActive($tab)) ? ' class="active"' : '' }}><a href="{{{ route($tab['route'][0], $tab['route'][1]) }}}">{{{ $tab['title'] }}}</a></li>
		@endif
	@endforeach
	
@if(!$forMainMenu)
</ul>
@endif

@endif