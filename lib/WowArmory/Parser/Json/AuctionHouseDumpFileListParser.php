<?php

namespace WowArmory\Parser\Json;

/*
 * first we request a url which provides a LIST of auction file urls. we later fetch one of those urls(they contain the actual auction data)
 * 
 * this class is for the LIST of files.
 * 
 */


class AuctionHouseDumpFileListParser extends WowApiJsonParser
{
    protected $realm;
    protected $structureValidationRules = [
        ['files.*.lastModified', 'f']
        , ['files.*.url', [__CLASS__, 'looksLikeUrl']]
    ];

    function __construct($jsonText, $region, $realm)
    {
        parent::__construct($jsonText, $region);
        $this->realm = $realm;
    }

    function realm()
    {
        return $this->realm;
    }

    function files()
    {
        $files = [];
        foreach ($this->jsonObj->files as $entry) {
            $files[] = ['lastModified' => $entry->lastModified, 'url' => $entry->url];
        }

        return $files;
    }

    function mostRecentFile()
    {
        $files = $this->files();
        usort($files, function ($a, $b) {
            return $b['lastModified'] - $a['lastModified'];
        });

        return isset($files[0]) ? $files[0] : null;
    }

    function valid()
    {
        return count($this->files());
    }

    static function looksLikeUrl($str)
    {
        return (bool)preg_match('~^http\://.+\.json$~D', $str);
    }

    function __toString()
    {
        return sprintf(
            "%s: num files: %d, mostRecentFile: '%s'"
            , __CLASS__
            , count($this->files())
            , $this->mostRecentFile()
        );
    }


}