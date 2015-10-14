<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPositionToAttachments extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasColumn('text_block_attachments','position')) {
			Schema::table('text_block_attachments', function($table) {
				$table->integer('position')->unsigned();
			});
			
			foreach (DB::table('text_block_attachments')->get(array('id')) as $item) {
				DB::table('text_block_attachments')->where('id', $item->id)->update(array('position' => $item->id));
			}
			
			Schema::table('text_block_attachments', function($table) {
				$table->index(array ('text_block_id','position'));
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
		if (Schema::hasColumn('text_block_attachments', 'position')) {
			Schema::table('text_block_attachments', function ($table) {
				$table->index(array ('text_block_id'));
				$table->dropUnique('text_block_attachments_text_block_id_position_unique');
				$table->dropColumn('position');
			});
		}
	}

}
