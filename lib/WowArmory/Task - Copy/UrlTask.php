<?php

namespace WowArmory\Task;

interface UrlTask
{
    public function getUrl();

    public function update($url, $url_payload, $http_response_code);
}