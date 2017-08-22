<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserNotificationsTable extends Migration
{

    public function up()
    {
        Schema::create(
            'user_notifications', function (Blueprint $table) {
                $table->string('id');
                $table->foreign('id')->references('id')->on('notifications')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
                $table->string('user_id');
                $table->foreign('user_id')->references('user_id')->on('users');
                $table->boolean('slack');
                $table->boolean('email');
                $table->timestamps();
                $table->unique(['id', 'user_id']);
            }
        );
    }

    public function down()
    {
        Schema::dropIfExists('user_notifications');
    }
}
