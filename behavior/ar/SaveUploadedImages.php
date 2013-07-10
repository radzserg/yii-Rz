<?php

namespace Rz\behavior\ar;

use \Rz\helper\Image;

/**
 * This behavior helps you to save model images transparently.
 * $model->save() it's all you need to do
 *
 *
 * @package Rz\behavior\ar
 * @author radzserg
 * @property CActiveRecord $owner CActiveRecord
 */
class SaveUploadedImages extends \CActiveRecordBehavior
{

    // we could upload image from file or url
    const IMAGE_SOURCE_FILE = 'file';
    const IMAGE_SOURCE_URL = 'url';

    /**
     * model attributes that has images
     * @var type
     */
    public $attributes = array();

    /**
     * Do we need to delete image folder after deletion of all copies. Default true
     * @var bool
     */
    public $deleteFolder = true;

    /**
     *
     * @var array (attribute => file or url )
     */
    public $_imageSources;

    protected $_oldImages;
    protected $_imageUrls;


    /**
     * Set source(url|file) for attribute
     * @param $attribute
     * @param $source
     * @throws \CException
     */
    public function setImageSource($attribute, $source)
    {
        if (!in_array($source, array(self::IMAGE_SOURCE_FILE, self::IMAGE_SOURCE_URL))) {
            throw new \CException("Undefined image source {$source}");
        }
        $this->_imageSources[$attribute] = $source;
    }

    /**
     * Save old image
     * @param  $event
     * @return void
     */
    public function afterFind($event)
    {
        /* @var $model CActiveRecord */
        $model = $this->owner;
        $attributes = $this->attributes;
        foreach ($attributes as $attribute) {
            $this->_oldImages[$attribute] = $model->$attribute;
        }
    }

    /**
     * Define is image was really saved
     *
     * @param  $event
     * @return void
     */
    public function beforeValidate($event)
    {
        /* @var $model CActiveRecord */
        $model = $this->owner;
        $attributes = $this->attributes;

        foreach ($attributes as $attribute) {
            $imageSource = isset($this->_imageSources[$attribute]) ?
                $this->_imageSources[$attribute] : self::IMAGE_SOURCE_FILE;

            if ($imageSource == self::IMAGE_SOURCE_URL) {
                $this->_imageUrls[$attribute] = $model->$attribute;
            }

            if (!$this->_oldImages[$attribute]) {
                $photoFile = \CUploadedFile::getInstance($model, $attribute);
                if ($photoFile) {
                    $model->$attribute = 'loading';
                }
            } else {
                $model->$attribute = $this->_oldImages[$attribute];
            }
        }
    }

    /**
     * Save an uploaded file if given, after removing possible other files.
     */
    public function afterSave($event)
    {
        /* @var $model CActiveRecord */
        $model = $this->owner;
        $attributes = $this->attributes;

        $model->setIsNewRecord(false);
        $this->setEnabled(false);

        foreach ($attributes as $attribute) {
            $imageSource = isset($this->_imageSources[$attribute]) ?
                $this->_imageSources[$attribute] : self::IMAGE_SOURCE_FILE;

            if ($imageSource == self::IMAGE_SOURCE_FILE) {
                Image::saveModelImage($model, $model->getSavePathFolder($attribute), $attribute);
            } elseif ($imageSource == self::IMAGE_SOURCE_URL) {
                Image::saveModelImageByUrl($model, $model->getSavePathFolder($attribute), $this->_imageUrls[$attribute], $attribute);
            }

        }

        $this->setEnabled(true);
    }

    /**
     * Delete the file on delete.
     */
    public function afterDelete($event)
    {
        /* @var $model CActiveRecord */
        $model = $this->owner;
        $attributes = $this->attributes;

        foreach ($attributes as $attribute) {
            $imagePath = $model->getImagePath($attribute);
            if ($imagePath) {
                Image::clearCopies($imagePath, $this->deleteFolder);
            }
        }
    }

    /**
     * Check is file uploaded
     * @param $attribute
     * @return bool
     */
    public function isFileUploaded($attribute)
    {
        $model = $this->owner;
        $photoFile = \CUploadedFile::getInstance($model, $attribute);
        return $photoFile ? true : false;
    }

}
