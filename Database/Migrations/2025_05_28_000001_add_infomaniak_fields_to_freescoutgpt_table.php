<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInfomaniakFieldsToFreescoutgptTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('freescoutgpt', function (Blueprint $table) {
            $table->boolean('infomaniak_enabled')->default(false)->after('responses_api_prompt');
            $table->string('infomaniak_api_key')->nullable()->after('infomaniak_enabled');
            $table->string('infomaniak_product_id')->nullable()->after('infomaniak_api_key');
            $table->string('infomaniak_model')->nullable()->after('infomaniak_product_id');
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
            $table->dropColumn('infomaniak_enabled');
            $table->dropColumn('infomaniak_api_key');
            $table->dropColumn('infomaniak_product_id');
            $table->dropColumn('infomaniak_model');
        });
    }
}
