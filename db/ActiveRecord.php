<?php

namespace Rz\db;

/**
 * Extension for ActiveRecord
 *
 * @package Rz\db
 */
class ActiveRecord extends \CActiveRecord
{


    /**
     * This is what yii2 does
     * @param $data
     * @return bool
     */
    public function populate($data)
    {
        $modelName = \CHtml::modelName($this);
        if (isset($data[$modelName])) {
            $this->attributes = $data[$modelName];
            return true;
        } else {
            return false;
        }
    }

    /**
     * Static Model method using php 5.3
     * @param string $className
     * @return \CActiveRecord
     */
    public static function model($className=__CLASS__)
    {
		return parent::model(get_called_class());
    }

}
