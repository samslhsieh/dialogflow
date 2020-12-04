<?php

namespace Samslhsieh\Dialogflow;

use Google\Protobuf\Value;
use Google\Protobuf\Struct;
use Google\Cloud\Dialogflow\V2\TextInput;
use Google\Cloud\Dialogflow\V2\QueryInput;
use Google\Cloud\Dialogflow\V2\QueryParameters;
use Google\Cloud\Dialogflow\V2\SessionsClient;
use Illuminate\Support\Facades\Storage;
use Samslhsieh\Dialogflow\Exceptions\DialogflowException;

class Dialogflow
{
    protected $options = [
        'key'           => null,
        'projectName'   => null,
        'languageCode'  => null,
        'sessionId'     => null,
        'text'          => null,
        'optionalArgs'  => []
    ];

    protected SessionsClient $sessionsClient;
    protected string $session;
    protected string $text;
    protected $queryResult;
    protected $message;

    public function __construct(array $options = [])
    {
        if (isset($options['key'])) {
            $this->setKey($options['key']);
            unset($options['key']);
        }

        $this->options = array_merge($this->options, $options);
    }

    public function fetch($text = null)
    {
        if (!isset($this->options['projectName'])) {
            throw DialogflowException::parameterIsEmpty('projectName');
        }

        if (!isset($this->options['languageCode'])) {
            throw DialogflowException::parameterIsEmpty('languageCode');
        }

        $text = $text ?? $this->options['text'];
        if (!isset($text) || $text === '') {
            throw DialogflowException::parameterIsEmpty('text');
        }

        $textInput = new TextInput();
        $textInput->setText($text);
        $textInput->setLanguageCode($this->options['languageCode']);

        $queryInput = new QueryInput();
        $queryInput->setText($textInput);

        $queryParams = $this->formatOptionalArgs($this->options['optionalArgs']);

        $this->init();
        $response = $this->sessionsClient->detectIntent(
            $this->session, $queryInput, $queryParams
        );
        $this->close();

        $this->queryResult = $response->getQueryResult();

        $this->text = $this->queryResult->getFulfillmentText();
        $this->message = $this->queryResult->getFulfillmentMessages();

        return $this->text;
    }

    private function init()
    {
        if (!isset($this->options['key']) || empty($this->options['key'])) {
            throw DialogflowException::credentialNotFound();
        }

        $this->sessionsClient = new SessionsClient([ 'credentials' => $this->options['key'] ]);
        $this->session = $this->sessionsClient->sessionName(
            $this->options['projectName'], $this->options['sessionId'] ?? uniqid()
        );
    }

    private function close()
    {
        $this->sessionsClient instanceof SessionsClient ?
            $this->sessionsClient->close() : null;
    }

    private function formatOptionalArgs(array $optionalArgs)
    {
        if (empty($optionalArgs)) {
            return [];
        }

        $googleStruct = new Struct();
        $googleStruct->setFields(
            array_map(function ($optionArg) {
                $gValue = new Value();
                $gValue->setStringValue($optionArg);
                return $gValue;
            }, $optionalArgs)
        );

        $queryParameters = new QueryParameters();
        $queryParameters->setPayload($googleStruct);

        return [ 'queryParams' => $queryParameters ];
    }

    protected function withOption($key, $value)
    {
        $this->options[ $key ] = $value;

        return $this;
    }

    public function setKey($key)
    {
        if (is_array($key)) {
            return $this->withOption('key', $key);
        }

        if ($key instanceof \stdClass) {
            $key = json_decode(json_encode($key), true);
            return $this->withOption('key', $key);
        }

        if (Storage::disk('local')->exists($key)) {
            $key = Storage::get($key);
            return $this->withOption('key', json_decode($key, true));
        }

        if (file_exists($key)) {
            $key = file_get_contents($key);
            return $this->withOption('key', json_decode($key, true));
        }

        if (is_string($key)) {
            $key = json_decode($key, true);
            if (is_array($key)) {
                return $this->withOption('key', $key);
            }
        }

        throw DialogflowException::keyUnknowType();
    }

    public function setProjectName($projectName)
    {
        return $this->withOption('projectName', $projectName);
    }

    public function setLanguageCode($languageCode)
    {
        return $this->withOption('languageCode', $languageCode);
    }

    public function setText($text)
    {
        return $this->withOption('text', $text);
    }

    public function setOptionalArgs(array $optionalArgs = [])
    {
        return $this->withOption('optionalArgs', $optionalArgs);
    }

    /**
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    public function getInstance()
    {
        return $this;
    }

    public function getQueryResult()
    {
        return $this->queryResult;
    }

    public function getParameters()
    {
        if (!isset($this->queryResult)) {
            return null;
        }

        $json = json_decode($this->queryResult->serializeToJsonString(), true);
        return $json['parameters'];
    }

    public function toJson()
    {
        if (!isset($this->queryResult)) {
            return null;
        }

        return json_decode($this->queryResult->serializeToJsonString(), true);
    }
}