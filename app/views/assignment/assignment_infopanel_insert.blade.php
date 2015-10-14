
<?php $warnings = $assignment->getEditorWarnings(); ?>

@if( !empty($warnings) )
<div class="panel">
<div class="panel-body bg-warning">

<ul>
@foreach ($warnings as $warning)
    <li class="add-bottom-margin">{{{ $warning }}}</li>
@endforeach
</ul>

</div>
</div>

@endif