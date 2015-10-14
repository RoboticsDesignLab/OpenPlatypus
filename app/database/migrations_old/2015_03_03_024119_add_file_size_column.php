<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFileSizeColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasColumn('disc_files','size')) {
			Schema::table('disc_files', function($table) {
				$table->bigInteger('size')->unsigned()->index();
			});
			
			foreach (DB::table('disc_files')->get(array('disk_file_name')) as $filename) {
				$realfile = DiscFile::$baseDir . '/'. $filename->disk_file_name;
				$size = filesize($realfile);
				DB::table('disc_files')->where('disk_file_name', $filename->disk_file_name)->update(array('size' => $size));
			}
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		if (Schema::hasColumn('disc_files', 'size')) {
			Schema::table('disc_files', function ($table) {
				$table->dropColumn('size');
			});
		}
	}

}
