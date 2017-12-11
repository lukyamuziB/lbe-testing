<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFileSessionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("session_file", function (Blueprint $table) {
            $table->integer("session_id");
            $table->integer("file_id");

            $table->primary(["session_id", "file_id"]);
            $table->foreign("session_id")->references("id")->on("sessions");
            $table->foreign("file_id")->references("id")->on("files");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop("session_file");
    }
}
