<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSessionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('request_id')->unsigned()->index();
            $table->timestamp('date');
            $table->string('start_time');
            $table->string('end_time');
            $table->boolean('mentee_approved');
            $table->boolean('mentor_approved');
            $table->timestamp('mentee_logged_at')->nullable();
            $table->timestamp('mentor_logged_at')->nullable();
            $table->foreign('request_id')->references('id')->on('requests');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sessions');
    }
}
