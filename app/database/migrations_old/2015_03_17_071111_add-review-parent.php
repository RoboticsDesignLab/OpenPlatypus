<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReviewParent extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasColumn('reviews','parent_id')) {
			Schema::table('reviews', function($table) {
				$table->integer('parent_id')->unsigned()->nullable();
				$table->foreign('parent_id')->references('id')->on('reviews')->onDelete('cascade');
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
		if (Schema::hasColumn('reviews','parent_id')) {
			Schema::table('reviews', function($table) {
				$table->dropColumn('parent_id');
			});
		}
		
	}

}
