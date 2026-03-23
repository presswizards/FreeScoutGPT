<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLitellmFieldsToFreescoutgptTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('freescoutgpt', function (Blueprint $table) {
            $table->boolean('litellm_enabled')->default(false);
            $table->string('litellm_base_url', 255)->nullable();
            $table->string('litellm_api_key', 255)->nullable();
            $table->string('litellm_model', 255)->nullable();
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
            $table->dropColumn([
                'litellm_enabled',
                'litellm_base_url',
                'litellm_api_key',
                'litellm_model',
            ]);
        });
    }
}
