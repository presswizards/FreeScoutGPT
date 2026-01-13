<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\AIAssistant\Http\Controllers'], function()
{
    // Core AI generation routes
    Route::post('/aiassistant/generate', 'AIAssistantController@generate')->middleware('auth');
    Route::get('/aiassistant/answers', 'AIAssistantController@answers')->middleware('auth');
    Route::get('/aiassistant/is_enabled', 'AIAssistantController@checkIsEnabled');

    // Tone adjustment
    Route::post('/aiassistant/adjust-tone', 'AIAssistantController@adjustTone')->middleware('auth');

    // Customer context routes
    Route::get('/aiassistant/customer-context', 'AIAssistantController@getCustomerContextApi')->middleware('auth');
    Route::post('/aiassistant/refresh-context', 'AIAssistantController@refreshCustomerContext')->middleware('auth');

    // Model listing
    Route::post('/aiassistant/get-models', 'AIAssistantController@getAvailableModels');

    // Settings routes (admin only)
    Route::get('/mailbox/{mailbox_id}/aiassistant-settings', [
        'uses' => 'AIAssistantController@settings',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin']
    ])->name('aiassistant.settings');

    Route::post('/mailbox/{mailbox_id}/aiassistant-settings', [
        'uses' => 'AIAssistantController@saveSettings',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin']
    ]);

    // Legacy routes for backward compatibility
    Route::post('/freescoutgpt/generate', 'AIAssistantController@generate')->middleware('auth');
    Route::get('/freescoutgpt/answers', 'AIAssistantController@answers')->middleware('auth');
    Route::get('/freescoutgpt/is_enabled', 'AIAssistantController@checkIsEnabled');
    Route::post('/freescoutgpt/get-models', 'AIAssistantController@getAvailableModels');
});
