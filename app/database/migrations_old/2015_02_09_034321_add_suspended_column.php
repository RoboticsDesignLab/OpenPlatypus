<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Platypus\Helpers\PlatypusBool;

class AddSuspendedColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (! Schema::hasColumn('subject_members', 'suspended')) {
			Schema::table('subject_members', function ($table) {
				$table->tinyInteger('suspended')->unsigned()->default(PlatypusBool::false);
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
		Schema::table('subject_members', function($table)
		{
			$table->dropColumn('suspended');
		});
	}

}
