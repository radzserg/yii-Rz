<?php

namespace Rz\console;

/**
 * Extended yii console command
 *
 * @author: radzserg
 * @date: 11.04.11
 */

/**
 * Extended yii console command.
 * - Add color support.
 * - Allow to run singleton scripts
 * - Note the time
 *
 *
 * @package Rz\console
 * @author: radzserg
 */
class Command extends \CConsoleCommand
{

    const VERBOSE_ERROR = 'error';
    const VERBOSE_INFO = 'info';
    const VERBOSE_SYSTEM = 'system';

    public $verbose;

    private $_lockFile;

    // if false this means that multiple scripts can work simultaneously

    protected $_isSingletonScript = false;

    // calculate time execution time

    protected $_timeStart;

    protected function _verbose($message, $level=null, $type=null)
    {
        if (!$this->verbose) {
            return ;
        }

        $level = (int)$level;
        $indent = str_repeat("\t", $level);
        if ($type == self::VERBOSE_ERROR) {
            // message in red
            $message = "\033[31;1m" . $message . "\033[0m\n";
        } elseif ($type == self::VERBOSE_INFO) {
            // message in green
            $message = "\033[32;1m" . $message . "\033[0m\n";
        } elseif ($type == self::VERBOSE_SYSTEM) {
            $message = "\033[33;1m" . $message . "\033[0m\n";
        }

        echo $indent . date('H:i:s ') . $message . "\n";
    }

    protected function beforeAction($action,$params)
    {
        $this->_verbose("Start execution of " . get_class($this), null, self::VERBOSE_SYSTEM);
        $this->_timeStart = $this->_microtimeFloat();
        if ($this->_isSingletonScript) {
            $lockDir = \Yii::getPathOfAlias('application.commands.lock');
            if (!is_dir($lockDir)) {
                mkdir($lockDir);
            }
            $filePath = $lockDir . '/' . get_class($this) . '.lock';
            $this->_lockFile = fopen($filePath, "w");
            if (!flock($this->_lockFile, LOCK_EX | LOCK_NB)) {
                $this->_verbose("Another instance of this script is running");
                return false;
            }
        }
        return true;
    }


    protected function afterAction($action,$params,$exitCode=0)
    {
        if ($this->_lockFile) {
            flock($this->_lockFile, LOCK_UN);
        }
        $time = round($this->_microtimeFloat() - $this->_timeStart, 2);
        $this->_verbose("End (time: {$time} seconds)", null, self::VERBOSE_SYSTEM);
    }

    private function _microtimeFloat()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
}