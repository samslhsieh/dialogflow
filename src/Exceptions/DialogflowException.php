<?php

namespace Samslhsieh\Dialogflow\Exceptions;

use Exception;

class DialogflowException extends Exception
{
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        if (empty($message)) {
            $message = "Failed to Dialogflow Service.";
        }
        parent::__construct($message, $code, $previous);
    }

    public static function parameterIsEmpty(string $param)
    {
        return new static("Invalid parameter. `$param` is empty.");
    }

    public static function credentialNotFound()
    {
        return new static("Credential not found");
    }

    public static function keyUnknowType()
    {
        return new static("Invalid `Key` type");
    }
}
