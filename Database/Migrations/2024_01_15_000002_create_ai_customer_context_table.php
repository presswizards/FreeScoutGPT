<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAiCustomerContextTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ai_customer_context', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('customer_id');
            $table->unsignedInteger('mailbox_id');
            $table->text('context_summary')->nullable();
            $table->text('common_issues')->nullable(); // JSON encoded array
            $table->string('communication_style', 50)->nullable();
            $table->text('preferences')->nullable(); // JSON encoded object
            $table->timestamp('last_analyzed_at')->nullable();
            $table->timestamps();

            // Indexes for faster lookups
            $table->index('customer_id');
            $table->index('mailbox_id');
            $table->unique(['customer_id', 'mailbox_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ai_customer_context');
    }
}
