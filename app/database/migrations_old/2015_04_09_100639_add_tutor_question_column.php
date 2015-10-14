<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTutorQuestionColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (! Schema::hasColumn('assignment_tutors', 'question_id')) {
			Schema::table('assignment_tutors', function ($table) {
				
				$table->integer('question_id')->unsigned()->nullable();
				$table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
				
				$table->unique(array (
						'assignment_id',
						'subject_member_id',
						'question_id' 
				), 'assignment_tutors_all_unique');
			});
			
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		if (Schema::hasColumn('assignment_tutors','question_id')) {
			Schema::table('assignment_tutors', function($table) {
				$table->dropForeign('assignment_tutors_question_id_foreign');
				$table->dropColumn('question_id');
			});
		}
	}

}
