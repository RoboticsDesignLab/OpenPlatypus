<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;



class ChangeStudentGroupMembersColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (! Schema::hasColumn('student_group_memberships', 'subject_member_id')) {
			Schema::table('student_group_memberships', function ($table) {
				$table->integer('subject_member_id')->unsigned();
				$table->foreign('subject_member_id')->references('id')->on('subject_members')->onDelete('cascade');
			});
		}
		if (Schema::hasColumn('student_group_memberships', 'user_id')) {
			Schema::table('student_group_memberships', function ($table) {
				$table->dropForeign('student_group_memberships_user_id_foreign');
				$table->dropColumn('user_id');
			});
		}
		
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		if (! Schema::hasColumn('student_group_memberships', 'user_id')) {
			Schema::table('student_group_memberships', function ($table) {
				$table->integer('user_id')->unsigned();
				$table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
			});
		}
		if (Schema::hasColumn('student_group_memberships', 'subject_member_id')) {
			Schema::table('student_group_memberships', function ($table) {
				$table->dropForeign('student_group_memberships_subject_member_id_foreign');
				$table->dropColumn('subject_member_id');
			});
		}
	}
}

