<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MarkingTaskGroups extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('review_groups')) {
			ReviewGroup::createTable();
		}
				
		if (Schema::hasColumn('reviews', 'parent_id')) {
			Schema::table('reviews', function($table) {
				$table->dropForeign('reviews_parent_id_foreign');
				$table->dropColumn('parent_id');
			});
		}
					

		if (!Schema::hasColumn('reviews', 'review_group_id')) {
			Schema::table('reviews', function($table) {
				$table->integer('review_group_id')->unsigned()->nullable();
				$table->foreign('review_group_id')->references('id')->on('review_groups')->onDelete('set null');
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
		Schema::dropIfExists('review_groups');
		
		if (Schema::hasColumn('reviews', 'review_group_id')) {
			Schema::table('reviews', function($table) {
				$table->dropColumn('review_group_id');
			});
		}		
		
	}

}
