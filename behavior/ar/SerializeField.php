<?php

namespace Rz\behavior\ar;

/**
 * This behavior serialize and unserialize specified fields beforeSave and afterFind
 *
 * Class SerializeField
 * @package Rz\behavior\ar
 * @author radzserg
 */
class SerializeField extends \CActiveRecordBehavior
{

    public $fields = array(); // fields that should be serialized


    public function afterFind($event)
    {
        foreach ($this->fields as $attribute) {
            if (!empty($this->owner->$attribute)) {
                $this->owner->$attribute = unserialize($this->owner->$attribute);
            }
        }
    }

    public function beforeSave($event)
    {
        foreach ($this->fields as $attribute) {
            if (is_array($this->owner->$attribute)) {
                $this->owner->$attribute = serialize($this->owner->$attribute);
            }
        }
    }

    public function afterSave($event)
    {
        foreach ($this->fields as $attribute) {
            if (!empty($this->owner->$attribute)) {
                $this->owner->$attribute = unserialize($this->owner->$attribute);
            }
        }
    }

}