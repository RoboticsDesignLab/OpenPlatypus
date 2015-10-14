<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReviewFlag extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasColumn('reviews','flag')) {
			Schema::table('reviews', function($table) {
				$table->tinyInteger('flag')->default(ReviewFlag::none);
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
		if (Schema::hasColumn('reviews', 'flag')) {
			Schema::table('reviews', function ($table) {
				$table->dropColumn('flag');
			});
		}
	}

}
