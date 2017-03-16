<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRequestSkillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_skills', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('request_id')->unsigned()->index();
            $table->integer('skill_id')->unsigned()->index();
            $table->timestamps();

            $table->foreign('request_id')->references('id')->on('requests');
            $table->foreign('skill_id')->references('id')->on('skills');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('request_skills');
    }
}
