<?php

namespace WowArmory\Task;

use WowArmory\Task\UrlTask;

class CallbackPoweredUrlTask implements UrlTask
{
    protected $updateFunc, $url;

    public function __construct($url, $updateFunc)
    {
        $this->updateFunc = $updateFunc;
        $this->url = $url;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function update($url, $url_payload, $http_response_code)
    {
        call_user_func($this->updateFunc, $url, $url_payload, $http_response_code);
    }
}