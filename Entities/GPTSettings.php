<?php

namespace Modules\FreeScoutGPT\Entities;

use Illuminate\Database\Eloquent\Model;

class GPTSettings extends Model
{
    protected $table = 'freescoutgpt';

    public $timestamps = false;

    protected $primaryKey = 'mailbox_id';

    public $incrementing = false;

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
    ];
}