<?php

namespace Rz\helper;

use Symfony\Component\Yaml\Yaml as smYaml;

/**
 * Yaml decoder from Symfony
 *
 * @require Symfony\Component\Yaml\Yaml
 * @author: radzserg
 * @date: 05.10.11
 */
class Yaml
{

    /**
     * Load YAML
     * @static
     * @param $input file|yaml formatted text
     * @return array
     */
    public static function parse($input)
    {
        $sfYaml = new smYaml();
        return $sfYaml->parse($input);
    }

    /**
     * Dump array into YAML formatted text
     * @static
     * @param $array
     * @return string
     */
    public static function dump($array)
    {
        $sfYaml = new smYaml();
        return $sfYaml->dump($array);
    }

}
