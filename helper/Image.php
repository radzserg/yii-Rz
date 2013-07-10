<?php

namespace Rz\helper;

/**
 * Helper that upload, save, resize model images
 *
 * @requires http://www.yiiframework.com/extension/image/ extension
 */
class Image
{

    /**
     * Dynamically create image with specified sizes and return its URL
     *
     * @param $originalImage
     * @param null $width
     * @param null $height
     * @return string
     */
    static public function getImageSrc($originalImage, $width = null, $height = null)
    {
        if (!file_exists($originalImage)) {
            return '';
        }
        $pathInfo = pathinfo($originalImage);

        if ($width || $height) {
            $resizeFile = "{$pathInfo['dirname']}/{$pathInfo['filename']}_w{$width}_h{$height}.{$pathInfo['extension']}";
        } else {
            $resizeFile = "{$pathInfo['dirname']}/{$pathInfo['filename']}.{$pathInfo['extension']}";
        }

        $basePath = dirname(\Yii::app()->basePath);
        $resizeFileSrc = str_replace($basePath, '', $resizeFile);
        $resizeFileSrc = str_replace(DS, '/', $resizeFileSrc);
        if (!file_exists($resizeFile)) {
            $image = \Yii::app()->image->load($originalImage);
            $image->resize($width, $height)
                ->quality(100);
            $image->save($resizeFile);
        }

        return $resizeFileSrc;
    }

    /**
     * Save image for model
     *
     * @param $model
     * @param $path
     * @param string $attribute
     * @throws \CException
     */
    static public function saveModelImage($model, $path, $attribute = 'image')
    {
        $photoFile = \CUploadedFile::getInstance($model, $attribute);
        // save photo if it was uploaded
        if ($photoFile) {
            $imageExt = $photoFile->getExtensionName();

            self::clearCopies($path . "/" . $model->$attribute);

            // save time snap in order to reset browser cache
            $model->$attribute = "{$model->id}_" . time() . ".{$imageExt}";
            $newPhotoPath = $path . "/" . $model->$attribute;

            $photoFile->saveAs($newPhotoPath, false);

            $image = \Yii::app()->image->load($newPhotoPath);
            /* @var $image Image */

            $width = $image->width;
            $height = $image->height;

            // if image exceeds max sizes - resize it
            if ($width > MAX_IMAGE_WIDTH || $height > MAX_IMAGE_HEIGHT) {
                $image->resize(MAX_IMAGE_WIDTH, MAX_IMAGE_HEIGHT)
                    ->quality(100);
                $image->save($newPhotoPath);
            }

            if (!$model->save()) {
                throw new \CException("Can't update model, errors - " . print_r($model->getErrors()));
            }
        }
    }

    /**
     * Save image for model by downloading from specified url
     *
     * @param $model
     * @param $path
     * @param $url
     * @param string $attribute
     * @throws \CException
     */
    static public function saveModelImageByUrl($model, $path, $url, $attribute = 'image')
    {
        // copy to cache
        $cachePath = \Yii::app()->params['preuploadImagePath']  . '/' . mt_rand(1, 999999);
        file_put_contents($cachePath, file_get_contents($url));

        $image = \Yii::app()->image->load($cachePath);
        /* @var $image \Image */
        $imageExt = $image->ext;

        self::clearCopies($path . "/" . $model->$attribute);

        $model->$attribute = "{$model->id}_" . time() . ".{$imageExt}";
        $newPhotoPath = $path . "/" . $model->$attribute;

        copy($cachePath, $newPhotoPath);
        //chmod($newPhotoPath, 0666);
        // if image is too big resize it
        $width = $image->width;
        $height = $image->height;

        if ($width > MAX_IMAGE_WIDTH || $height > MAX_IMAGE_HEIGHT) {
            $image->resize(MAX_IMAGE_WIDTH, MAX_IMAGE_HEIGHT)
                ->quality(100);
            $image->save($newPhotoPath);
        }
        if (!$model->save()) {
            throw new \CException("Can't update model, errors - " . print_r($model->getErrors()));
        }
    }

    /**
     * Clear resized image copies
     *
     * @param $originalImage
     * @param bool $deleteFolder
     * @return string
     */
    static public function clearCopies($originalImage, $deleteFolder = false)
    {
        if (!file_exists($originalImage)) {
            return '';
        }
        $pathInfo = pathinfo($originalImage);

        $files = glob("{$pathInfo['dirname']}/{$pathInfo['filename']}_*", GLOB_NOSORT);
        foreach ($files as $file) {
            unlink($file);
        }
        unlink($originalImage);

        if ($deleteFolder && \Rz\helper\File::isDirEmpty($pathInfo['dirname'])) {
            rmdir($pathInfo['dirname']);
        }
    }

    /**
     * Move image
     * @param $originalImage
     * @param $toDir
     * @throws \Exception
     */
    static public function copyImageToDir($originalImage, $toDir)
    {
        $newImage = $toDir . '/' . basename($originalImage);
        if (!copy($originalImage, $newImage)) {
            throw new \Exception("Can't move {$originalImage} to {$newImage}");
        }
    }

}
