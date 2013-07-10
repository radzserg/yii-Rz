<?php

namespace Rz\helper;

/**
 * Added some functions that I didn't find in CFileHelper
 * @package Rz\helper
 */
class File
{

     /**
     * @param string $dir path to dir to remove
     */
    public static function removeFilesInDirRecursive($dir)
    {
        $files = glob($dir . DIRECTORY_SEPARATOR . '{,.}*', GLOB_MARK | GLOB_BRACE);

        foreach ($files as $file) {
            if (basename($file) == '.' || basename($file) == '..') {
                continue;
            }

            if (substr($file, - 1) == DIRECTORY_SEPARATOR) {
                self::removeDirRecursive($file);
            } else {
                unlink($file);
            }
        }
    }

    /**
     * @param string $dir path to dir to remove
     */
    public static function removeDirRecursive($dir)
    {
        $files = glob($dir . DIRECTORY_SEPARATOR . '{,.}*', GLOB_MARK | GLOB_BRACE);

        foreach ($files as $file) {
            if (basename($file) == '.' || basename($file) == '..') {
                continue;
            }

            if (substr($file, - 1) == DIRECTORY_SEPARATOR) {
                self::removeDirRecursive($file);
            } else {
                unlink($file);
            }
        }

        if (is_dir($dir)) {
            rmdir($dir);
        }
    }

    public static function mkdir($dst,array $options = array(), $recursive = true)
	{
		$prevDir=dirname($dst);
		if($recursive && !is_dir($dst) && !is_dir($prevDir))
			self::mkdir(dirname($dst),$options,true);

		$mode=isset($options['newDirMode']) ? $options['newDirMode'] : 0777;
		$res=mkdir($dst, $mode);
		chmod($dst,$mode);
		return $res;
	}

    public static function isDirEmpty($dir)
    {
        return (count(scandir($dir)) == 2);
    }

}