<?php 

$totalCount = $assignment->getUserReviewsQuery($user)->count();
$completedCount = $assignment->getUserReviewsCompletedQuery($user)->count();

if ($completedCount >= $totalCount) {
	$badgeClass = "badge-success";
} else if ($completedCount > 0) {
	$badgeClass = "badge-info";
} else {
	$badgeClass = "badge-warning";
}
	
?>

@if($totalCount > 0)
	<span class="badge {{{ $badgeClass }}}">{{{ $completedCount }}} / {{{ $totalCount }}}</span>
@endif