<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("request_users", function (Blueprint $table) {
            $table->increments("id");
            $table->string("user_id");
            $table->integer("role_id");
            $table->integer("request_id");
            $table->foreign("user_id")->references("user_id")->on("users");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("request_users");
    }
}
