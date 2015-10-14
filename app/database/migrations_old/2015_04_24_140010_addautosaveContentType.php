<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddautosaveContentType extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasColumn('autosave_texts','content_type')) {
			Schema::table('autosave_texts', function($table) {
				$table->tinyInteger('content_type')->default(TextBlockContentType::plain);
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
		if (Schema::hasColumn('autosave_texts', 'content_type')) {
			Schema::table('autosave_texts', function ($table) {
				$table->dropColumn('content_type');
			});
		}
	}

}
