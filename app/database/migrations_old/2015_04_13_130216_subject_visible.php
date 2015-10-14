<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SubjectVisible extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasColumn('subjects', 'visibility')) {
			Schema::table('subjects', function ($table) {
				$table->tinyInteger('visibility')->default(0)->index();
			});
		}
		if (Schema::hasColumn('subjects', 'visible')) {
			Schema::table('subjects', function ($table) {
				$table->dropColumn('visible');
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
		if (Schema::hasColumn('subjects', 'visibility')) {
			Schema::table('subjects', function ($table) {
				$table->dropColumn('visibility');
				$table->tinyInteger('visible')->default(0)->index();
			});
		}
	}

}
