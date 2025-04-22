<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddResponsesApiAndArticleUrlsToFreescoutgptTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('freescoutgpt', function (Blueprint $table) {
            $table->boolean('use_responses_api')->default(false);
            $table->text('article_urls')->nullable();
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
            $table->dropColumn('use_responses_api');
            $table->dropColumn('article_urls');
        });
    }
}
