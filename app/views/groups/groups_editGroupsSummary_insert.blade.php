 @if(isset($group)) 
 	<ul class="list-group compact">
 		@foreach($group->users as $user)
 			<li class="list-group-item">{{{ $user->presenter()->name }}}</li>	
 		@endforeach
 	</ul>
 @endif
