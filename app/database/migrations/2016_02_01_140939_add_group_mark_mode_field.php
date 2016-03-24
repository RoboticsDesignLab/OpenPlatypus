<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Platypus\Model\Assignment;

class AddGroupMarkModeField extends Migration {

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
			$table->tinyInteger('group_mark_mode')->default(0);
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
			//
			$table->dropColumn('group_mark_mode');
		});
	}

}
