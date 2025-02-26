<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeStartMessageColumnInFreescoutgptTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('freescoutgpt', function (Blueprint $table) {
            $table->text('start_message')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('freescoutgpt', function (Blueprint $table) {
            $table->string('start_message')->change();
        });
    }
}