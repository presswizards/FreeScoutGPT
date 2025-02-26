<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddClientDataEnabledColumnToFreescoutgptTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('freescoutgpt', function (Blueprint $table) {
            $table->boolean('client_data_enabled');
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
            $table->dropColumn('client_data_enabled');
        });
    }
}
