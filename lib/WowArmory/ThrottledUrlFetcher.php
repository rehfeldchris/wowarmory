<?php

namespace WowArmory;

use WowArmory\Task\UrlTaskProvider;
use WowArmory\Throttler\Throttler;


class ThrottledUrlFetcher
{
    protected $taskProvider;
    protected $throttler;
    protected $curl;

    public function __construct(UrlTaskProvider $provider, Throttler $throttler)
    {
        $this->taskProvider = $provider;
        $this->throttler = $throttler;
        $this->curl = curl_init();
        curl_setopt_array($this->curl, [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT      => 'just a janky home grown spider. plz dont bant me, i behave',
            CURLOPT_ENCODING       => 'gzip,deflate'
        ]);
    }

    public function run()
    {
        $provider = $this->taskProvider;
        while ($this->shouldKeepRunning()) {
            $urlTask = $provider->getNextUrlTask();
            if ($urlTask) {
                $url = $urlTask->getUrl();
                do {
                    $result = $this->download($url);
                    $this->throttler->sleep();
                } while (curl_errno($this->curl) > 0);
                echo $url, ' ', $result['http_response_code'], "\n";
                $urlTask->update($url, $result['url_payload'], $result['http_response_code']);
            } else {
                $this->throttler->sleep();
            }

        }
    }

    protected function download($url)
    {
        $ch = $this->curl;
        curl_setopt($ch, CURLOPT_URL, $url);
        $url_payload = curl_exec($ch);
        $http_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return compact('url_payload', 'http_response_code');
    }

    protected function shouldKeepRunning()
    {
        //@TODO maybe check file or listen for signals to shut down
        return true;
    }


}
