<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RecreateAssignmentMarks extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasColumn('assignment_marks', 'id')) {
			Schema::dropIfExists('assignment_marks');
			AssignmentMark::createTable();
		}
		
		Schema::dropIfExists('subject_marks');		
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		//
	}

}
