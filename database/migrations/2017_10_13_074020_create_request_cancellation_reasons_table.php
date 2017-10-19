<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRequestCancellationReasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("request_cancellation_reasons", function (Blueprint $table) {
            $table->increments("id");
            $table->integer("request_id")->unsigned()->index();
            $table->string("user_id");
            $table->text("reason");
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
        Schema::dropIfExists("request_cancellation_reasons");
    }
}
