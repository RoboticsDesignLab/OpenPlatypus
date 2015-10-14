<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Platypus\Helpers\PlatypusBool;

class CreateStudentGroupSuggestionsTables extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('student_group_suggestions')) {
			StudentGroupSuggestion::createTable();
		}
		
		Schema::dropIfExists('student_group_selection_tokens');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		if (! Schema::hasTable('student_group_selection_tokens')) {
			Schema::create('student_group_selection_tokens', function ($table) {
				$table->engine = 'InnoDB';
				$table->timestamps();
				
				$table->integer('user_id')->unsigned();
				$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
				
				$table->integer('assignment_id')->unsigned();
				$table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('cascade');
				
				$table->string('token', 200)->unique();
				
				$table->timestamp('expiry')->index();
			});
		}
		
		Schema::dropIfExists('student_group_suggestion_memberships');
		Schema::dropIfExists('student_group_suggestions');
	}

}
