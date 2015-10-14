<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSolutionEditorField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasColumn('questions','solution_editor_id')) {
			Schema::table('questions', function($table) {
				$table->integer('solution_editor_id')->unsigned()->nullable()->default(NULL);
				$table->foreign('solution_editor_id')->references('id')->on('subject_members')->onDelete('set null');
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
		if (Schema::hasColumn('questions', 'solution_editor_id')) {
			Schema::table('questions', function ($table) {
				$table->dropForeign('questions_solution_editor_id_foreign');
				$table->dropColumn('solution_editor_id');
			});
		}
	}

}
