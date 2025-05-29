<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInfomaniakApiPromptToFreescoutgptTable extends Migration
{
    public function up()
    {
        Schema::table('freescoutgpt', function (Blueprint $table) {
            $table->text('infomaniak_api_prompt')->nullable()->after('infomaniak_model');
        });
    }

    public function down()
    {
        Schema::table('freescoutgpt', function (Blueprint $table) {
            $table->dropColumn('infomaniak_api_prompt');
        });
    }
}
