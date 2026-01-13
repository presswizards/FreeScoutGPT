<?php

namespace Modules\AIAssistant\Entities;

use Illuminate\Database\Eloquent\Model;

class AISettings extends Model
{
    /**
     * The table associated with the model.
     * Using the legacy table name for backward compatibility.
     */
    protected $table = 'freescoutgpt';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'mailbox_id';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'mailbox_id',
        'api_key',
        'token_limit',
        'start_message',
        'enabled',
        'model',
        'client_data_enabled',
        'use_responses_api',
        'article_urls',
        'responses_api_prompt',
        // New customer history learning fields
        'customer_history_enabled',
        'history_depth',
        'context_refresh_interval',
        'analysis_model',
        'context_prompt_template',
        'auto_draft_enabled',
        'draft_notification_type',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'enabled' => 'boolean',
        'client_data_enabled' => 'boolean',
        'use_responses_api' => 'boolean',
        'customer_history_enabled' => 'boolean',
        'auto_draft_enabled' => 'boolean',
        'history_depth' => 'integer',
        'token_limit' => 'integer',
    ];

    /**
     * Default settings values.
     */
    public static function getDefaults()
    {
        return [
            'enabled' => false,
            'api_key' => '',
            'token_limit' => 1024,
            'start_message' => '',
            'model' => 'gpt-4o-mini',
            'client_data_enabled' => false,
            'use_responses_api' => false,
            'article_urls' => '',
            'responses_api_prompt' => '',
            'customer_history_enabled' => false,
            'history_depth' => 10,
            'context_refresh_interval' => 'weekly',
            'analysis_model' => 'gpt-4o-mini',
            'context_prompt_template' => '',
            'auto_draft_enabled' => false,
            'draft_notification_type' => 'banner',
        ];
    }

    /**
     * Get settings with defaults merged.
     */
    public static function getWithDefaults($mailbox_id)
    {
        $settings = self::find($mailbox_id);
        $defaults = self::getDefaults();

        if (!$settings) {
            $defaults['mailbox_id'] = $mailbox_id;
            return (object) $defaults;
        }

        foreach ($defaults as $key => $value) {
            if (!isset($settings->$key) || $settings->$key === null) {
                $settings->$key = $value;
            }
        }

        return $settings;
    }
}
