<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create(
            'ratings', function (Blueprint $table) {
                $table->integer('session_id')->unsigned();
                $table->foreign('session_id')->references('id')->on('sessions');

                $table->string('user_id')->unsigned();
                $table->foreign('user_id')->references('user_id')->on('users');

                $table->json('values');
                $table->integer('scale');
                $table->timestamps();

                $table->primary(array('session_id', 'user_id'));
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */

    public function down()
    {
        Schema::dropIfExists('ratings');
    }
}
