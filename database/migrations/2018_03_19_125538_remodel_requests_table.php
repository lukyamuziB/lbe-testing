<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemodelRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table("requests", function (Blueprint $table) {
            $table->renameColumn("mentee_id", "created_by");
            $table->integer("request_type_id")->default(2);
            $table->dropColumn("mentor_id");
            $table->foreign('created_by')->references('user_id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("requests", function (Blueprint $table) {
            $table->renameColumn("created_by", "mentee_id");
            $table->dropColumn("request_type_id");
            $table->string("mentor_id")->nullable();
        });
    }
}
