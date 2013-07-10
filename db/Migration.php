<?php

namespace Rz\db;

class BaseMigration extends \CDbMigration
{
}

class ChangeMigration
{

    const DIRECTION_UP = 'up';
    const DIRECTION_DOWN = 'down';

    /**
     * Specify direction for change function
     * @var up|down
     */
    private $_change;

    /**
     * Queue of change actions
     * @var type
     */
    private $_changeDownQueue = array();

    public function up()
	{
        $transaction = $this->getDbConnection()->beginTransaction();
        try {

            if (method_exists($this, 'change')) {
                $this->_change = self::DIRECTION_UP;
                $this->change();
            } else {
                if ($this->safeUp() === false) {
                    $transaction->rollback();
                    return false;
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            echo "Exception: " . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ")\n";
            echo $e->getTraceAsString() . "\n";
            $transaction->rollback();
            return false;
        }
        return null;
	}

    public function down()
	{
        $transaction = $this->getDbConnection()->beginTransaction();
        try {
            if (method_exists($this, 'change')) {
                $this->_change = self::DIRECTION_DOWN;
                $this->change();
                $this->_afterChangeDown();
            } else {
                if ($this->safeDown() === false) {
                    $transaction->rollback();
                    return false;
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            echo "Exception: " . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ")\n";
            echo $e->getTraceAsString() . "\n";
            $transaction->rollback();
            return false;
        }
        return null;
	}

    public function safeUp()
    {
    }

    public function safeDown()
    {
    }

    public function setDbConnection($db)
	{
		self::_baseMigration()->setDbConnection($db);
	}

    public function getDbConnection()
	{
        return self::_baseMigration()->getDbConnection();
    }

    private static function _baseMigration()
    {
        return new \Rz\db\BaseMigration();
    }

    /**
     * Call function in reverse order
     */
    private function _afterChangeDown()
    {
        $this->_change = null;
        foreach (array_reverse($this->_changeDownQueue) as $function) {
            /* @var $function \Closure */
            $function();
        }
    }

    public function execute($sql, $params = array(), \Closure $down = null)
    {
        if (!$this->_change || $this->_change == self::DIRECTION_UP) {
            return self::_baseMigration()->execute($sql, $params);
        } else {
            if ($down) {
                $this->_changeDownQueue[] = $down;
            }
        }
    }

    public function insert($table, $columns, \Closure $down = null)
    {
        if (!$this->_change || $this->_change == self::DIRECTION_UP) {
            return self::_baseMigration()->insert($table, $columns);
        } else {
            if ($down) {
                $this->_changeDownQueue[] = $down;
            }
        }
    }

    public function update($table, $columns, $condition = '',
        $params = array(), \Closure $down = null)
    {
        if (!$this->_change || $this->_change == self::DIRECTION_UP) {
            return self::_baseMigration()->update($table, $columns, $condition, $params);
        } else {
            if ($down) {
                $this->_changeDownQueue[] = $down;
            }
        }
    }

    public function delete($table, $condition = '', $params = array(), \Closure $down = null)
    {
        if (!$this->_change || $this->_change == self::DIRECTION_UP) {
            return self::_baseMigration()->delete($table, $condition, $params);
        } else {
            if ($down) {
                $this->_changeDownQueue[] = $down;
            }
        }
    }

    public function createTable($table, $columns, $options = null)
    {
        if (!$this->_change || $this->_change == self::DIRECTION_UP) {
            self::_baseMigration()->createTable($table, $columns, $options);
        } else {
            $this->_changeDownQueue[] = function() use($table) {
                    return self::_baseMigration()->dropTable($table);
                };
        }
    }

    public function renameTable($table, $newName)
    {
        if (!$this->_change || $this->_change == self::DIRECTION_UP) {
            return self::_baseMigration()->renameTable($table, $newName);
        } else {
            $this->_changeDownQueue[] = function() use($newName, $table) {
                    return self::_baseMigration()->renameTable($newName, $table);
                };
        }
    }

    public function dropTable($table, \Closure $down = null)
    {
        if (!$this->_change || $this->_change == self::DIRECTION_UP) {
            return self::_baseMigration()->dropTable($table);
        } else {
            if ($down) {
                $this->_changeDownQueue[] = $down;
            }
        }
    }

    public function truncateTable($table, \Closure $down = null)
    {
        if (!$this->_change || $this->_change == self::DIRECTION_UP) {
            return self::_baseMigration()->truncateTable($table);
        } else {
            if ($down) {
                $this->_changeDownQueue[] = $down;
            }
        }
    }

    public function addColumn($table, $column, $type)
    {
        if (!$this->_change || $this->_change == self::DIRECTION_UP) {
            return self::_baseMigration()->addColumn($table, $column, $type);
        } else {
            $this->_changeDownQueue[] = function() use($table, $column) {
                    return self::_baseMigration()->dropColumn($table, $column);
                };
        }
    }

    public function dropColumn($table, $column, \Closure $down = null)
    {
        if (!$this->_change || $this->_change == self::DIRECTION_UP) {
            return self::_baseMigration()->dropColumn($table, $column);
        } else {
            if ($down) {
                $this->_changeDownQueue[] = $down;
            }
        }
    }

    public function renameColumn($table, $name, $newName)
    {
        if (!$this->_change || $this->_change == self::DIRECTION_UP) {
            return self::_baseMigration()->renameColumn($table, $name, $newName);
        } else {
            $this->_changeDownQueue[] = function() use($table, $newName, $name) {
                    return self::_baseMigration()->renameColumn($table, $newName, $name);
                };
        }
    }

    public function alterColumn($table, $column, $type, \Closure $down = null)
    {
        if (!$this->_change || $this->_change == self::DIRECTION_UP) {
            return self::_baseMigration()->alterColumn($table, $column, $type);
        } else {
            if ($down) {
                $this->_changeDownQueue[] = $down;
            }
        }
    }

    public function addPrimaryKey($name, $table, $columns)
    {
        if (!$this->_change || $this->_change == self::DIRECTION_UP) {
            return self::_baseMigration()->addPrimaryKey($name, $table, $columns);
        } else {
            $this->_changeDownQueue[] = function() use($name, $table) {
                    return self::_baseMigration()->dropPrimaryKey($name, $table);
                };
        }
    }

    public function dropPrimaryKey($name, $table, \Closure $down = null)
    {
        if (!$this->_change || $this->_change == self::DIRECTION_UP) {
            return self::_baseMigration()->dropPrimaryKey($name, $table);
        } else {
            if ($down) {
                $this->_changeDownQueue[] = $down;
            }
        }
    }

    public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete = null, $update = null)
    {
        if (!$this->_change || $this->_change == self::DIRECTION_UP) {
            return self::_baseMigration()->addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete, $update);
        } else {
            $this->_changeDownQueue[] = function() use($name, $table) {
                    return self::_baseMigration()->dropForeignKey($name, $table);
                };
        }
    }

    public function dropForeignKey($name, $table, \Closure $down = null)
    {
        if (!$this->_change || $this->_change == self::DIRECTION_UP) {
            return self::_baseMigration()->dropForeignKey($name, $table);
        } else {
            if ($down) {
                $this->_changeDownQueue[] = $down;
            }
        }
    }

    public function createIndex($name, $table, $column, $unique = false)
    {
        if (!$this->_change || $this->_change == self::DIRECTION_UP) {
            return self::_baseMigration()->createIndex($name, $table, $column, $unique);
        } else {
            $this->_changeDownQueue[] = function() use($name, $table) {
                    return self::_baseMigration()->dropIndex($name, $table);
                };
        }
    }

    public function dropIndex($name, $table, \Closure $down = null)
    {
        if (!$this->_change || $this->_change == self::DIRECTION_UP) {
            return self::_baseMigration()->dropIndex($name, $table);
        } else {
            if ($down) {
                $this->_changeDownQueue[] = $down;
            }
        }
    }

    private function _notSupportedInChange($name)
    {
        throw new \Exception("Method {$name} is not supported in change migration");
    }


}