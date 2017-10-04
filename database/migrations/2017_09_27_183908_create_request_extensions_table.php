<?php
/**
 * File defines class for RequestExtension model
 * definition
 *
 * PHP version >= 7.0
 *
 * @category Database_Migrations
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class defines blueprint for RequestExtension model
 *
 * PHP version >= 7.0
 *
 * @category Database_Migrations
 */
class CreateRequestExtensionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            "request_extensions",
            function (Blueprint $table) {
                $table->increments("id")->unsigned();
                $table->integer("request_id");
                $table->foreign("request_id")->references("id")->on("requests");
                $table->boolean("approved")->nullable();
                $table->timestamps();
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
        Schema::dropIfExists("request_extensions");
    }
}
