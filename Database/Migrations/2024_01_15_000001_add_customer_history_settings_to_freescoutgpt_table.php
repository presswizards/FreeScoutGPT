<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCustomerHistorySettingsToFreescoutgptTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('freescoutgpt', function (Blueprint $table) {
            $table->boolean('customer_history_enabled')->default(false)->after('responses_api_prompt');
            $table->integer('history_depth')->default(10)->after('customer_history_enabled');
            $table->string('context_refresh_interval', 20)->default('weekly')->after('history_depth');
            $table->string('analysis_model', 50)->default('gpt-4o-mini')->after('context_refresh_interval');
            $table->text('context_prompt_template')->nullable()->after('analysis_model');
            $table->boolean('auto_draft_enabled')->default(false)->after('context_prompt_template');
            $table->string('draft_notification_type', 20)->default('banner')->after('auto_draft_enabled');
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
                'customer_history_enabled',
                'history_depth',
                'context_refresh_interval',
                'analysis_model',
                'context_prompt_template',
                'auto_draft_enabled',
                'draft_notification_type'
            ]);
        });
    }
}
