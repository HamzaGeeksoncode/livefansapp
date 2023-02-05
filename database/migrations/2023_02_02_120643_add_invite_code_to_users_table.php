<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInviteCodeToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->string('invite_code')->after('platform')->nullable();
            $table->integer('limit_of_invite_code')->after('invite_code')->default(5)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropColumn('invite_code');
            $table->dropColumn('limit_of_invite_code');
        });
    }
}
