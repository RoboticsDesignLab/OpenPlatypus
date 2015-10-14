<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropReviewDueDate extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
			if (Schema::hasColumn('reviews', 'due_date')) {
			Schema::table('reviews', function ($table) {
				$table->unique(array ('answer_id','status'));
				$table->dropUnique('reviews_answer_id_status_due_date_unique');
				$table->dropColumn('due_date');
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
		if (!Schema::hasColumn('reviews','due_date')) {
			Schema::table('reviews', function($table) {
				$table->timestamp('due_date');
			});
		}
			
	}

}
