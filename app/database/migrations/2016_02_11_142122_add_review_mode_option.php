<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReviewModeOption extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('assignments', function(Blueprint $table)
		{
			//
			$table->tinyInteger('review_mode')->default(ReviewLimitMode::minreviewlimit);
			$table->integer('min_assigned_reviews')->unsigned()->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('assignments', function(Blueprint $table)
		{
			$table->dropColumn('review_mode');
			$table->dropColumn('min_assigned_reviews');
		});
	}

}
