<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMessageEditPromptToFreescoutgptTable extends Migration
{
    public function up()
    {
        Schema::table('freescoutgpt', function (Blueprint $table) {
            $table->text('message_edit_prompt')->nullable();
        });
    }

    public function down()
    {
        Schema::table('freescoutgpt', function (Blueprint $table) {
            $table->dropColumn('message_edit_prompt');
        });
    }
}

