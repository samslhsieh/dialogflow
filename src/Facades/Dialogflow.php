<?php


namespace Samslhsieh\Dialogflow\Facades;

use Illuminate\Support\Facades\Facade;

class Dialogflow extends Facade
{
    protected static function getFacadeAccessor()
    {
        return "Dialogflow";
    }
}