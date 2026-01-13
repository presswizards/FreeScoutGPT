<?php

namespace Modules\AIAssistant\Providers;

use App\Mailbox;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use App\Thread;
use Modules\AIAssistant\Entities\AISettings;
use Modules\AIAssistant\Jobs\GenerateAutoDraft;
use Nwidart\Modules\Facades\Module;

class AIAssistantServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Save the mailbox for re-use in the javascripts hook.
     */
    private $mailbox = null;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            array_push($javascripts, \Module::getPublicPath("aiassistant").'/js/module.js');
            return $javascripts;
        });

        \Eventy::addAction('layout.head', function () {
            echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
          crossorigin="anonymous" referrerpolicy="no-referrer" />' . PHP_EOL;
        });

        // Add module's CSS file to the application layout.
        \Eventy::addFilter('stylesheets', function($stylesheets) {
            array_push($stylesheets, \Module::getPublicPath("aiassistant").'/css/module.css');
            return $stylesheets;
        });

        // Catch the mailbox for the current request
        \Eventy::addFilter('mailbox.show_buttons', function($show, $mailbox){
            $this->mailbox = $mailbox;
            return $show;
        }, 20, 2);

        // JavaScript in the bottom
        \Eventy::addAction('javascript', function() {
            $module = Module::find('aiassistant');
            $version = $module ? $module->get('version') : '';
            $copiedToClipboard = __("Copied to clipboard");
            $updateAvailable = __('Update available for module ');
            $settings = $this->mailbox ? AISettings::find($this->mailbox->id) : null;
            $start_message = $settings ? addslashes($settings->start_message) : "";
            $responses_api_prompt = $settings ? addslashes($settings->responses_api_prompt) : "";
            $modifyPrompt = __("Complete prompt and send last response from client to AI");
            $send = __("Generate Answer");

            // Customer history settings
            $customerHistoryEnabled = $settings ? ($settings->customer_history_enabled ?? false) : false;
            $historyDepth = $settings ? ($settings->history_depth ?? 10) : 10;

            echo "const aiAssistantData = {" .
                    "'copiedToClipboard': '{$copiedToClipboard}'," .
                    "'updateAvailable': '{$updateAvailable}'," .
                    "'version': '{$version}'," .
                    "'start_message': `{$start_message}`," .
                    "'responses_api_prompt': `{$responses_api_prompt}`," .
                    "'modifyPrompt': `{$modifyPrompt}`," .
                    "'send': `{$send}`," .
                    "'customerHistoryEnabled': " . ($customerHistoryEnabled ? 'true' : 'false') . "," .
                    "'historyDepth': {$historyDepth}," .
                "};";
            echo 'aiAssistantInit();';
        });

        \Eventy::addAction('mailboxes.settings.menu', function($mailbox) {
            if (auth()->user()->isAdmin()) {
                echo \View::make('aiassistant::partials/settings_menu', ['mailbox' => $mailbox])->render();
            }
        }, 80);

        \Eventy::addAction('thread.menu', function ($thread) {
            if ($thread->type == Thread::TYPE_LINEITEM) {
                return;
            }
            ?>
            <li><a class="ai-assistant-generate" href="#" target="_blank" role="button"><?php echo __("Generate Answer (AI)")?></a></li>
            <?php
        }, 100);

        // Hook for conversation sidebar - AI Assistant panel
        \Eventy::addAction('conversation.after_subject', function ($conversation) {
            $settings = AISettings::find($conversation->mailbox_id);
            if ($settings && $settings->enabled) {
                echo \View::make('aiassistant::partials/conversation_panel', [
                    'conversation' => $conversation,
                    'settings' => $settings
                ])->render();
            }
        }, 10);

        // Hook for auto-draft generation when customer replies
        \Eventy::addAction('thread.created', function ($thread) {
            // Only process customer messages
            if ($thread->type != Thread::TYPE_CUSTOMER) {
                return;
            }

            $conversation = $thread->conversation;
            if (!$conversation) {
                return;
            }

            $settings = AISettings::find($conversation->mailbox_id);
            if (!$settings || !$settings->enabled || !$settings->auto_draft_enabled) {
                return;
            }

            // Dispatch the auto-draft job
            GenerateAutoDraft::dispatch($conversation->id, $thread->id)
                ->delay(now()->addSeconds(5)); // Small delay to ensure thread is fully saved
        }, 20);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTranslations();
        $this->registerCommands();
    }

    /**
     * Register console commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\AIAssistant\Console\Commands\RefreshCustomerContext::class,
            ]);
        }
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('aiassistant.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'aiassistant'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/aiassistant');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/aiassistant';
        }, \Config::get('view.paths')), [$sourcePath]), 'aiassistant');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ .'/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
