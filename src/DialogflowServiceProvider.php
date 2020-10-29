<?php

namespace Samslhsieh\Dialogflow;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class DialogflowServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/dialogflow.php' => config_path('dialogflow.php'),
        ], "config");
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton("Dialogflow", function () {
            return new Dialogflow([
                'key'           => Config::get('dialogflow.key', null),
                'projectName'   => Config::get('dialogflow.project_name', null),
                'languageCode'  => Config::get('dialogflow.language_code', 'zh-tw')
            ]);
        });
    }
}
