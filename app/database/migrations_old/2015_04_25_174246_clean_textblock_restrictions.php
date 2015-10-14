<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CleanTextblockRestrictions extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (Schema::hasColumn('text_block_restrictions', 'category')) {
			Schema::table('text_block_restrictions', function ($table) {
				$table->dropColumn('category');
				$table->dropColumn('text_permitted');
				$table->dropColumn('text_max_length');
				$table->dropColumn('text_min_length');
				$table->dropColumn('file_upload_permitted');
				$table->dropColumn('file_size_max_bytes');
				$table->dropColumn('file_extension_allowed_1');
				$table->dropColumn('file_extension_allowed_2');
				$table->dropColumn('file_extension_allowed_3');
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
		//
	}

}
