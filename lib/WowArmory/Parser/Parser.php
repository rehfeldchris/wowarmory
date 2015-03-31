<?php

/**
 * All Parsers should be able to tell if they succeeded, whether they parse json, html, or somethign else.
 */

namespace WowArmory\Parser;

interface Parser
{
    /**
     * Indicates whether the parser thinks it succeeded.
     *
     * @return boolean
     */
    public function valid();
}