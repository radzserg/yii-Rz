<?php

namespace Rz\behavior\ar;

use \Rz\helper\Image,
    \Rz\helper\File;

/**
 * Implements methods for model attributes that represent images.
 *
 * You need to specify UPLOAD_IMAGE_PATH - base path where you save/upload images
 *
 *  'hasImages' => array(
 *      'class' => '\Rz\behavior\ar\HasImages',
 *      'attributes' => array(
 *          'image' => BRAND_IMAGES_URL,    // relative path where we will save "image" attribute images
 *          'image_logo' => BRAND_IMAGE_LOGOS_URL,  // relative path where we will save "image_logo" attribute images
 *      ),
 *  )
 *  or with only one attribute
 *   'hasImages' => array(
 *      'class' => '\Rz\behavior\ar\HasImages',
 *      'attributes' => array(
 *          'image' => USER_PROFILE_URL
 *      ),
 *   )
 *
 *
 * I assume that each such model attribute stores value {$model->id}.{$imageExtension}
 * Model know
 *
 *
 * @package Rz\behavior\ar
 * @author radzserg
 */
class HasImages extends \CActiveRecordBehavior
{

    /**
     * model attribute => path map
     * @var array
     */
    public $attributes = array();
    public $defaultAttribute = 'image';


    /**
     * Return model image path
     *
     * @param null $attribute use defaultAttribute if not specified
     * @return null|string
     */
    public function getImagePath($attribute = null)
    {
        if (!$attribute) {
            $attribute = $this->defaultAttribute;
        }

        $model = $this->owner;
        if (!$model->$attribute) {
            return null;
        }

        $path = $this->getSavePathFolder($attribute) . DS . $model->$attribute;
        if (file_exists($path)) {
            return $path;
        } else {
            return null;
        }
    }

    /**
     * Return image url
     *
     * @param array $params
     *  - attribute for what attribute do we need to get src, Bu default use defaultAttribute
     *  - width - if specified will resize image
     *  - height - if specified will resize image
     *  - default - if image doesn't exists - return default src
     * @return null
     */
    public function getImageSrc($params = array())
    {
        $attribute = isset($params['attribute']) ? $params['attribute'] : $this->defaultAttribute;
        $width = isset($params['width']) ? (int)$params['width'] : null;
        $height = isset($params['height']) ? (int)$params['height'] : null;
        $defaultSrc = isset($params['default']) ? $params['default'] : null;

        $imagePath = $this->getImagePath($attribute);

        if ($imagePath) {
            return Image::getImageSrc($imagePath, $width, $height);
        } elseif ($defaultSrc) {
            return $defaultSrc;
        }
    }

    /**
     * Return path where we gonna save model images
     *
     * @param null $attribute - use defaultAttribute if not specified
     * @return string
     */
    public function getSavePathFolder($attribute = null)
    {
        if (!$attribute) {
            $attribute = $this->defaultAttribute;
        }

        $model = $this->owner;
        $path = realpath(UPLOAD_IMAGE_PATH . $this->attributes[$attribute]) . DS . $model->id;
        if (!is_dir($path)) {
            File::mkdir($path);
        }

        return realpath($path);
    }

}
