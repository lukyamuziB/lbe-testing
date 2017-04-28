<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->increments('id');
            $table->string('mentee_id');
            $table->string('mentor_id')->nullable();
            $table->string('title');
            $table->text('description');
            $table->json('interested')->nullable();
            $table->integer('status_id')->unsigned();
            $table->timestamp('match_date')->nullable();
            $table->json('pairing');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('requests');
    }
}
