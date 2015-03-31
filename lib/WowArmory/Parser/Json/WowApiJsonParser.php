<?php

/**
 * Base class which provides common json structure and rule validation
 * for subclasses which will do the specific parsing.
 *
 * strings provided by this class will be utf8 encoded.
 *
 * In general, the blizzard api doesnt provide high quality json responses.
 * sometimes theyre just plain invalid json syntax, and other times
 * theyre "close" to most of the other responses, but maybe missing certain attributes or other bizzare anomalies.
 * So, we proceed with some fuzzy validation and error correction.
 */

namespace WowArmory\Parser\Json;

use WowArmory\Parser\Parser;

abstract class WowApiJsonParser implements Parser
{
    protected $jsonText
    , $jsonObj
    , $region
    , $structureValidationRules = [];//subclass will define rules, if any

    /**
     *
     * @param type $jsonText the text to parse
     * @param type $region   the blizzard game server region. examples would be "us" or "eu"
     *                       etc...
     * @throws \InvalidArgumentException if parsing fails, or if a validation constraint isnt met.
     */
    function __construct($jsonText, $region = null)
    {
        $this->jsonText = $jsonText;
        $this->jsonObj = json_decode($jsonText);
        $err = json_last_error();
        $this->region = $region;

        //sometimes its missing the last }, so we add it back and see if that helps...ugh
        if ($err === JSON_ERROR_SYNTAX) {
            $this->jsonObj = json_decode($jsonText . '}');
            $err = json_last_error();
            if ($err === JSON_ERROR_NONE) {
                $this->jsonText = $jsonText . '}';
            }
        }
        if ($err !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException(sprintf(
                "jsonText parsing failed json_error'%s' text:'%s'"
                , $err
                , $jsonText
            ));
        }

        if (!is_object($this->jsonObj)) {
            throw new \InvalidArgumentException(sprintf(
                "jsonText parsing failed. expected object, got: '%s'"
                , gettype($this->jsonObj)
            ));
        }

        if ($this->hasApiError()) {
            throw new \InvalidArgumentException(sprintf(
                "blizz api error: '%s'"
                , $this->jsonText
            ));
        }

        $sv = new SimpleArrayStructureValidator();
        $sv->addRules($this->structureValidationRules);
        $sv->validate(json_decode($this->jsonText, true));
        if (!$sv->validate(json_decode($this->jsonText, true))) {
            var_dump($this->jsonObj->name);
            throw new \InvalidArgumentException(sprintf(
                "jsonText structure validation failed. failing rules encoded as json: '%s' jsonText: '%s'"
                , json_encode($sv->getFailingRules())
                , $this->jsonText
            ));
        }
    }

    /**
     *
     *
     * @return boolean
     */
    function hasApiError()
    {
        return isset($this->jsonObj->status)
        && $this->jsonObj->status === "nok";
    }

    function getApiErrorReason()
    {
        return $this->isApiError()
            ? $this->jsonObj->reason
            : null;
    }

    function region()
    {
        return $this->region;
    }


}