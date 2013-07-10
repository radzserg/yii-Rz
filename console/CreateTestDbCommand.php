<?php

namespace Rz\console;

use Yii;


/**
 * Create test db from live db.
 *
 * WARNING: this works only for Mysql Server.
 *
 * This command will use main config and will copy db structure to test db. It's very convenient to run it recreate
 * DB structure for tests
 *
 * You also can specify in \Yii::app()->params['static_test_tables'] in this case this script will also copy data from
 * specified tables. It's useful for static tables, like acl, user role, etc.
 *
 *
 * @package Rz\console
 * @author radzserg
 */
class CreateTestDbCommand extends Command
{

    public $verbose = true;
    private $_dumpPath;
    private $_dumpDataPath;
    private $_dumpTables = array();

    public function init()
    {
        $this->_dumpPath = \Yii::app()->basePath . '/data/db/schema.sql';
        $this->_dumpDataPath = \Yii::app()->basePath . '/data/db/data.sql';
        $this->_dumpTables = \Yii::app()->params['static_test_tables'];

        return parent::init();
    }

    public function actionIndex()
    {
        $this->_verbose("Creating test DB");

        $this->_makeDumpFromLiveDb();

        $this->_createTestDb();

        $this->_verbose("Test DB has been successfully created");
    }

    protected function _makeDumpFromLiveDb()
    {
        $this->_verbose("Making dump from live DB");
        $config = $this->_getLiveDbConfig();
        $loginOptions = "--user={$config['username']} --password={$config['password']} "
            . "--host={$config['host']} {$config['dbname']}";
        $command = "mysqldump $loginOptions --no-data | sed 's/ AUTO_INCREMENT=[0-9]*\b//' > " . $this->_dumpPath;
        $this->_exec($command);

        $this->_verbose("Dump has been saved to {$this->_dumpPath}");


        if (count($this->_dumpTables)) {
            $this->_verbose("Create data dump for tables: " . implode(" ", $this->_dumpTables));
            $loginOptions = "--user={$config['username']} --password={$config['password']} "
                . "--host={$config['host']} {$config['dbname']}";
            $command = "mysqldump $loginOptions --no-create-info --tables "
                . implode(" ", $this->_dumpTables) . " > " . $this->_dumpDataPath;

            $this->_exec($command);
            $this->_verbose("Data dump has been saved to {$this->_dumpDataPath}");
        }

    }

    protected function _createTestDb()
    {
        $this->_verbose("Creating test DB");

        $config = $this->_getTestDbConfig();

        $this->_verbose("Clearing DB");
        $connection = new \CDbConnection($config['connectionString'], $config['username'], $config['password']);
        $existedTables = $connection->createCommand("SHOW TABLES")->queryColumn();
        $connection->createCommand("SET FOREIGN_KEY_CHECKS = 0;")->query();
        foreach ($existedTables as $table) {
            $connection->createCommand("DROP TABLE `{$table}`")->query();
        }

        $loginOptions = "--user={$config['username']} --password={$config['password']} "
            . "--host={$config['host']}";

        $command = "mysql {$config['dbname']} {$loginOptions} < " . $this->_dumpPath;

        $this->_exec($command);
        $this->_verbose("DB schema has been been copied");

        if (count($this->_dumpTables)) {
            $this->_verbose("Dumping data");
            $command = "mysql {$config['dbname']} {$loginOptions} < " . $this->_dumpDataPath;

            $this->_exec($command);
            $this->_verbose("DB data dump has been been copied");
        }
    }


    private function _exec($command)
    {
        $return = false;
        exec($command, $output, $return);
        $output = implode("\n", $output);
        if (!empty($output)) {
            $this->_verbose("Sorry an error occured. Details: {$output}");
            exit(1);
        }
    }


    private function _getLiveDbConfig()
    {
        $mainConfig = require Yii::app()->basePath . '/config/main.php';

        $config = $mainConfig['components']['db'];

        $m = array();
        preg_match('~mysql:host=(.+);dbname=(.+)~is', $config['connectionString'], $m);
        $config['host'] = $m[1];
        $config['dbname'] = $m[2];
        return $config;
    }

    private function _getTestDbConfig()
    {
        $testConfig = require Yii::app()->basePath . '/config/test.php';
        $testDbConfig = $testConfig['components']['db'];

        $m = array();
        preg_match('~mysql:host=(.+);dbname=(.+)~is', $testDbConfig['connectionString'], $m);
        $testDbConfig['host'] = $m[1];
        $testDbConfig['dbname'] = $m[2];

        return $testDbConfig;
    }


}