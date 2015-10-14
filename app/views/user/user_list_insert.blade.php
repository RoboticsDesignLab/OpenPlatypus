<?php 
	$paginate = $users instanceof Illuminate\Pagination\Paginator;
	
	if(!isset($clickableRows)) {
		$clickableRows = true;
	}
	
	if(!isset($stickyHeaders)) {
		$stickyHeaders = false;
	}
	
?>

@if($paginate)
{{ $users->links() }}
@endif


@if(count($users)>0)
<table class="table table-hover {{{ $stickyHeaders ? 'stickyHeaders' : ''}}}">
	<thead>
		<tr>
			@if(isset($buttonGenerator))
			<th></th>
			@endif
			
			<th>{{ $paginate ? makeSortableLink($users, 'first_name', 'First name') : 'First name' }}</th>
			<th>{{ $paginate ? makeSortableLink($users, 'last_name', 'Last name') : 'Last name' }}</th>
			@if(!isset($hideStudentId) || !$hideStudentId)
				<th>{{ $paginate ? makeSortableLink($users, 'student_id', 'Student ID ') : 'Student ID' }}</th>
			@endif
			@if(!isset($hideEmail) || !$hideEmail)
				<th>{{ $paginate ? makeSortableLink($users, 'email', 'Email') : 'Email' }}</th>
			@endif
			@if(isset($appendColumns))
				<?php if(!is_array($appendColumns)) { $appendColumns = array(array('title' => "", 'generator' => $appendColumns));} ?>
				@foreach($appendColumns as $column)
					<th>{{ $column['title'] }}</th>
				@endforeach
			@endif
		</tr>
	</thead>
	<tbody>
	
	
	<?php $counter = 0; ?>
	@foreach ($users as $user)
		<?php 
			$user = $user->presenter();

			if(isset($urlGenerator)) {
				$linkUrl = $urlGenerator($user);
			} else {
				$linkUrl = null;
			}
			
		?>
		
		@if($clickableRows && isset($linkUrl))
			<tr class='clickableRow' data-url="{{ $linkUrl }}">
		@else
			<tr>
		@endif
		
			@if(isset($buttonGenerator))
				<td>{{ $buttonGenerator($user) }}</td>
			@endif
			@if(isset($linkUrl))
				<td><a href="{{ $linkUrl }}">{{{ $user->first_name }}}</a></td>
				<td><a href="{{ $linkUrl }}">{{{ $user->last_name }}}</a></td>
			@else
				<td>{{{ $user->first_name }}}</td>
				<td>{{{ $user->last_name }}}</td>
			@endif
			@if(!isset($hideStudentId) || !$hideStudentId)
				<td>{{{ $user->student_id }}}</td>
			@endif
			@if(!isset($hideEmail) || !$hideEmail)		
				@if(isset($urlGenerator))	
					<td>{{{ $user->email }}}</td>
				@else
					<td>{{ HTML::mailto($user->email) }}</td>
				@endif
			@endif
			@if(isset($appendColumns))
				@foreach($appendColumns as $column)
					<td>{{ $column['generator']($user) }}</td>
				@endforeach
			@endif
			</tr>
	@endforeach
	</tbody>
</table>
@endif

@if($paginate)
	{{ $users->links() }}

	@if(($users->getLastPage() > 1) || ($users->getTotal() > 10))
		{{ makePerPageLinks($users) }}
	@endif
@endif


