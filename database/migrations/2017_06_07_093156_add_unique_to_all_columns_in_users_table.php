<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUniqueToAllColumnsInUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_id')->unique()->change();
            $table->string('slack_id')->unique()->change();       
            $table->string('email')->unique()->change();
        });        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(
            'users', function (Blueprint $table) {
                $table->dropUnique('users_user_id_unique');
                $table->dropUnique('users_slack_id_unique');
                $table->dropUnique('users_email_unique');
            }
        );
    }
}
