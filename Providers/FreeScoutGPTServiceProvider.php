<?php

namespace Modules\FreeScoutGPT\Providers;

use App\Mailbox;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use App\Thread;
use Modules\FreeScoutGPT\Entities\GPTSettings;
use Nwidart\Modules\Facades\Module;

class FreeScoutGPTServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    //save the mailbox for re-use in the javascripts hook
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
            array_push($javascripts, \Module::getPublicPath("freescoutgpt").'/js/module.js');
            return $javascripts;
        });

        \Eventy::addAction('layout.head', function () {
            echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" 
          crossorigin="anonymous" referrerpolicy="no-referrer" />' . PHP_EOL;
        });

        // Add module's CSS file to the application layout.
        \Eventy::addFilter('stylesheets', function($stylesheets) {
            array_push($stylesheets, \Module::getPublicPath("freescoutgpt").'/css/module.css');
            return $stylesheets;
        });

        //catch the mailbox for the current request
        \Eventy::addFilter('mailbox.show_buttons', function($show, $mailbox){
            $this->mailbox =$mailbox;
            return $show;
        }, 20 , 2);

        // JavaScript in the bottom
        \Eventy::addAction('javascript', function() {
            $module = Module::find('freescoutgpt');
            $version = $module ? $module->get('version') : '';
            $copiedToClipboard = __("Copied to clipboard");
            $updateAvailable = __('Update available for module ');
            $settings = $this->mailbox ? GPTSettings::find($this->mailbox->id) : null;
            $start_message = $settings ? $settings->start_message : "";
            $responses_api_prompt = $settings ? $settings->responses_api_prompt : "";
            $modifyPrompt = __("Complete prompt and send last response from client to GPT");
            $send = __("Generate Answer");

            echo "const freescoutGPTData = {" .
                    "'copiedToClipboard': '{$copiedToClipboard}'," .
                    "'updateAvailable': '{$updateAvailable}'," .
                    "'version': '{$version}'," .
                    "'start_message': `{$start_message}`," .
                    "'responses_api_prompt': `{$responses_api_prompt}`," .
                    "'modifyPrompt': `{$modifyPrompt}`," .
                    "'send': `{$send}`," .
                "};";
            echo 'freescoutgptInit();';
        });

        \Eventy::addAction('mailboxes.settings.menu', function($mailbox) {
            if (auth()->user()->isAdmin()) {
                echo \View::make('freescoutgpt::partials/settings_menu', ['mailbox' => $mailbox])->render();
            }
        }, 80);

        \Eventy::addAction('thread.menu', function ($thread) {
            if ($thread->type == Thread::TYPE_LINEITEM) {
                return;
            }
            ?>
            <li><a class="chatgpt-get" href="#" target="_blank" role="button"><?php echo __("Generate Answer (GPT)")?></a></li>
            <?php
        }, 100);

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTranslations();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('freescoutgpt.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'freescoutgpt'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/freescoutgpt');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/freescoutgpt';
        }, \Config::get('view.paths')), [$sourcePath]), 'freescoutgpt');
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
