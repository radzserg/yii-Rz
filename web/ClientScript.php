<?php

namespace Rz\web;


/**
 * Extension for ClientScript
 *
 * - Compile Less
 * - Compile coffee
 * - Generate JST from folder
 * - Other minor helpers
 *
 * @package Rz\web
 * @author radzserg
 *
 */
class ClientScript extends \CClientScript
{

    public $coffeePath = '/usr/bin/coffee';
    public $nodePath = null;

    /**
     * Should we compile less, coffee script or just used compiled files
     * true on production, false on dev
     * @var bool
     */
    public $compile;


    private $_activeScriptId;
    private $_activeScriptPosition;


    public function registerCssFile($url, $media = '')
    {
        $url = $this->_handleCssFile($url);
        return parent::registerCssFile($url, $media);
    }


    public function registerScriptFile($url, $position = null, array $htmlOptions = array())
    {
        $url = $this->_handleJsFile($url);
        return parent::registerScriptFile($url, $position);
    }

    /**
     * Add functionality to compile coffee scripts
     * @param $url
     * @return string
     */
    private function _handleJsFile($url)
    {
        if (preg_match('~^(.)*(.coffee)$~', $url)) {
            $newUrl = '/assets_compiled' . substr($url, 0, -7);
            if (!$this->compile) {
                return $newUrl;
            }
            $path = \Yii::getPathOfAlias('webroot') . $url;

            $filter = new \Assetic\Filter\CoffeeScriptFilter($this->coffeePath, $this->nodePath);
            $asset = new \Assetic\Asset\AssetCache(
                new \Assetic\Asset\FileAsset($path, array($filter)),
                new \Assetic\Cache\FilesystemCache(\Yii::getPathOfAlias('application.runtime.cache.assetic'))
            );

            $newPath = \Yii::getPathOfAlias('webroot') . $newUrl;
            $dir = dirname($newPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            file_put_contents($newPath, $asset->dump());

            return $newUrl;
        }
        return $url;
    }


    /**
     * Add functionality to compile less css files
     * @param $url
     * @return string
     */
    private function _handleCssFile($url)
    {
        if (preg_match('~^(.)*(.less)$~', $url)) {
            $newUrl = '/assets_compiled' . substr($url, 0, -5);
            if (!$this->compile) {
                return $newUrl;
            }

            $path = \Yii::getPathOfAlias('webroot') . $url;

            $filter = new \Assetic\Filter\LessphpFilter();
            //$filter = new \Assetic\Filter\LessFilter(null, array('/usr/local/lib/node_modules/'));
            $asset = new \Assetic\Asset\AssetCache(
                new \Assetic\Asset\FileAsset($path, array($filter)),
                new \Assetic\Cache\FilesystemCache(\Yii::getPathOfAlias('application.runtime.cache.assetic'))
            );

            $newPath = \Yii::getPathOfAlias('webroot') . $newUrl;
            $dir = dirname($newPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($newPath, $asset->dump());

            return $newUrl;
        }
        return $url;
    }

    /**
     * Generate JST(javascript templates) from webroot.jst folder
     * How likes to use _.template() will appreciate this trick
     *
     * @param null $subFolder
     */
    public function generateJst($subFolder = null)
    {
        if ($subFolder) {
            $jstUrl = "/assets_compiled/{$subFolder}/templates.js";
        } else {
            $jstUrl = '/assets_compiled/templates.js';
        }
        if (!$this->compile) {
            $this->registerScriptFile($jstUrl);
        } else {
            if (!$subFolder) {
                $basePath = \Yii::getPathOfAlias('webroot.jst');
            } else {
                $basePath = \Yii::getPathOfAlias('webroot.jst.' . $subFolder);
            }

            $files = \CFileHelper::findFiles($basePath, array('fileTypes' => array('jst')));
            $js = 'window.JST = {};';
            foreach ($files as $file) {
                $templateName = str_replace($basePath . DS, '', $file);
                $templateName = str_replace('\\', '/', $templateName);
                $templateName = str_replace('.jst', '', $templateName);
                $js .= "\n\nwindow.JST['{$templateName}'] = _.template(\""
                    . \CJavaScript::quote(file_get_contents($file)) . "\");";
            }
            file_put_contents(\Yii::getPathOfAlias('webroot') . $jstUrl, $js);
            $this->registerScriptFile($jstUrl);
        }
    }


    /**
     * Wrapper for javascript. This will helps IDE (like Netbeans) correctly find and parse javascript
     *
     * @param $pos
     * @param null $id
     * @throws \CException
     */
    public function beginScript($pos = parent::POS_READY, $id = null)
    {
        if ($this->_activeScriptId) {
            throw new \CException("You have nested beginScript function");
        }
        $id = $id ? $id : uniqid();
        $this->_activeScriptId = $id;
        $this->_activeScriptPosition = $pos;

        ob_start();
        ob_implicit_flush(false);
    }

    public function endScript()
    {

        $script = ob_get_clean();
        // $script = strip_tags($script, '<p><a><br><span><div><b><i><strong>');
        $script = preg_replace('~</?script[^>]*>\s*~', '', $script);
        parent::registerScript($this->_activeScriptId, $script, $this->_activeScriptPosition);
        $this->_activeScriptId = null;
    }



}