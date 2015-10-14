<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ReviewRandomColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
			if (!Schema::hasColumn('reviews','random')) {
			Schema::table('reviews', function($table) {
				
				$table->integer('random')->unsigned();
				
				$table->index(array (
						'user_id',
						'random',
						'id',
				));
				$table->index(array (
						'random',
						'id',
				));
				
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
		if (Schema::hasColumn('reviews', 'random')) {
			Schema::table('reviews', function ($table) {
				$table->dropColumn('random');
			});
		}
	}

}
