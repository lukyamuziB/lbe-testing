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
        Schema::dropColumn('user_id')->unique()->change();
        Schema::dropColumn('slack_id')->unique()->change();
        Schema::dropColumn('email')->unique()->change();        
    }
}
