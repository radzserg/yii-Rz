<?php

namespace Rz;

class Tools
{

    static public function underscoreToCamel($name)
    {
        $name = str_replace('_', ' ', $name);
        $name = ucwords($name);
        return str_replace(' ', '', $name);
    }

    public static function objectToArray($d)
    {
		if (is_object($d)) {
			// Gets the properties of the given object
			// with get_object_vars function
			$d = get_object_vars($d);
		}

		if (is_array($d)) {
			return array_map('\Rz\Tools::objectToArray', $d);
		} else {
			return $d;
		}
	}

}