<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFreescoutgptTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('freescoutgpt', function (Blueprint $table) {
            $table->integer('mailbox_id');
            $table->boolean('enabled');
            $table->string('api_key');
            $table->integer('token_limit');
            $table->string('start_message');
            $table->string('model');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('freescoutgpt');
    }
}
