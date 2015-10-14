
<div class="row">

@if($answer->submitted)
	<div class="col-md-12 add-bottom-margin">
		<strong>Submitted</strong>
	</div>
	
	@if($answer->isLate())
		<div class="col-md-12 add-bottom-margin">
			<div class="alert alert-danger add-bottom-margin">
				<strong>Submitted <br>{{{ $answer->presenter()->late_by }}} late</strong>
			</div>
		</div>
	@endif
	
	@if($answer->resource->mayRetract(Auth::user()))
		<div class="col-md-12 btn-group add-bottom-margin">
			{{ Form::post_button(array('route' => array('retractAnswerPost','answer_id' => $answer->id)), 'Retract answer', 'Do you realy want to retract your answer? After this you will have to re-submit your answer in time to have it marked.') }}
   		</div>
	@endif
	
	@if(isset($answer->resource->guessed_mark))
		<div class="col-md-12  add-bottom-margin">
			Your guessed mark was {{{ $answer->guessed_mark }}}%.
		</div>
	@endif
	
	
	
@else
   	<?php $errors = null; ?>
	@if($answer->resource->maySubmit(Auth::user(), true, $errors))
		<div class="col-md-12 btn-group add-bottom-margin">
			{{ Form::post_button_primary(
				array('route' => array('reviewAnswerPost','answer_id' => $answer->id)), 
				'Submit',
				array(
					'class' => "ifExistsSelector",
					'data-if-exists' => '.changed_textblock_'.$answer->text->id.',.empty_textblock_'.$answer->text->id,
					'data-if-exists-classes' => "disabled",
				)
			) }}
   		</div>
   		
   		<div 
			class="col-md-12 add-bottom-margin ifExistsSelector"
			data-if-exists=".changed_textblock_{{{ $answer->text->id }}},.empty_textblock_{{{ $answer->text->id }}}"
			data-if-exists-classes="hidden"
		>
			<div class="alert alert-danger" role="alert">
  				<strong>Important:</strong> you must press the submit button in order to finalise your answer.
			</div>
		</div>	
   		
   	@else
		<div class="col-md-12 add-bottom-margin">
			<strong>Not submitted</strong>
		</div>
   	@endif

@endif

</div>
	
