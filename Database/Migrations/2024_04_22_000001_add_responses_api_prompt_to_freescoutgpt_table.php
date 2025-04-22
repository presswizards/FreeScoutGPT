<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddResponsesApiPromptToFreescoutgptTable extends Migration
{
    public function up()
    {
        Schema::table('freescoutgpt', function (Blueprint $table) {
            $table->text('responses_api_prompt')->nullable();
        });
    }

    public function down()
    {
        Schema::table('freescoutgpt', function (Blueprint $table) {
            $table->dropColumn('responses_api_prompt');
        });
    }
}
