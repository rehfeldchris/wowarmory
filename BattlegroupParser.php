<?php

/**
 * Parses the json text for a battlegroup list,
 * providing the list of battlegroups that realms may be a part of.
 */
class BattlegroupParser extends WowApiJsonParser
{
    /**
     * Provides a list of battlegroup names.
     *
     * @return nested array of battlegroup names like [['name' => 'foo'], ['name' => 'bar']]
     * @throws RuntimeException if parsing failed
     */
    function battlegroups()
    {
        if (!isset($this->jsonObj->battlegroups) || !is_array($this->jsonObj->battlegroups)) {
            throw new RuntimeException("bad json, couldnt parse");
        }
        $bgs = [];
        foreach ($this->jsonObj->battlegroups as $bg) {
            if (!isset($bg->name)) {
                throw new RuntimeException("bad json obj, missing name");
            }
            $bgs[] = [
                'name' => $bg->name
            ];
        }

        return $bgs;
    }

    /**
     * Checks if data extraction seems succesful
     *
     * @return boolean
     */
    function valid()
    {
        try {
            return (bool)$this->battlegroups();
        } catch (Exception $e) {
            return false;
        }
    }

    function __toString()
    {
        return sprintf(
            "Battlegroups:  names:%s"
            , join(',', $this->battlegroups())
        );
    }

}