<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTextBlocksTextNullable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE `text_blocks` MODIFY `text` longtext NULL;');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('text_blocks', function(Blueprint $table)
		{
			DB::statement('ALTER TABLE `text_blocks` MODIFY `text` longtext NOT NULL;');
		});
	}

}
