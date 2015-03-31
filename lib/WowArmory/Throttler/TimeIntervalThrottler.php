<?php

namespace WowArmory\Throttler;

/*
 * This class lets you call its sleep() method blindly and it will:
 * -return immediately if more than $intervalSeconds have elapsed since 
 * the last call to sleep.
 * -sleep for long enough so that it wakes up after $intervalSeconds have
 * elapsed since the last call to the sleep() method.
 * 
 * basically, it only sleeps long enough to ensure that $intervalSeconds 
 * have passed since the last sleep call. This is useful for throttling, as it
 * will sleep if you have spare time, but sleep() becomes a no-op if you 
 * fall behind time-wise.
 */

class TimeIntervalThrottler implements Throttler
{
    protected $timestamp;
    protected $intervalSeconds;

    public function __construct($intervalSeconds)
    {
        if ($intervalSeconds < 0) {
            throw new InvalidArgumentException("interval cannot be negative");
        }
        $this->timestamp = microtime(true);
        $this->intervalSeconds = (float)$intervalSeconds;
    }

    function sleep()
    {
        $remainingSeconds = $this->getRemainingSeconds();
        if ($remainingSeconds > 0) {
            usleep($this->secondsToMicroseconds($remainingSeconds));
        }
        $this->timestamp = microtime(true);
    }

    protected function secondsToMicroseconds($seconds)
    {
        return $seconds * 1000000;
    }

    protected function getRemainingSeconds()
    {
        $startOfInterval = $this->timestamp;
        $endOfInterval = $this->timestamp + $this->intervalSeconds;
        $remainingSeconds = $endOfInterval - $startOfInterval;

        return max(0, $remainingSeconds);
    }
}