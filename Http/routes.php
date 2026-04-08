<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\FreeScoutGPT\Http\Controllers'], function()
{
    Route::post('/freescoutgpt/generate', 'FreeScoutGPTController@generate');
    Route::get('/freescoutgpt/answers', 'FreeScoutGPTController@answers');
    Route::get('/freescoutgpt/is_enabled', 'FreeScoutGPTController@checkIsEnabled');
    Route::get('/mailbox/{mailbox_id}/freescoutgpt-settings', ['uses' => 'FreeScoutGPTController@settings', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('freescoutgpt.settings');
    Route::post('/mailbox/{mailbox_id}/freescoutgpt-settings', ['uses' => 'FreeScoutGPTController@saveSettings', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']]);
    Route::post('/freescoutgpt/get-models', 'FreeScoutGPTController@getAvailableModels');
    Route::post('/freescoutgpt/infomaniak-models', 'FreeScoutGPTController@getAvailableInfomaniakModels');
    Route::post('/freescoutgpt/get-infomaniak-product-ids', 'FreeScoutGPTController@getAvailableInfomaniakProductIds');
});
