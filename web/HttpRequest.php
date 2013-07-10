<?php

namespace Rz\web;


/**
 * Extends HttpRequest
 *
 * Allows automatically decode JSON decoded requests
 * This helps to avoid problems with csrf check
 *
 *
 * @package Rz\web
 */
class HttpRequest extends \CHttpRequest
{

    protected $_restParams;

	/**
	 * Returns request parameters. Typically PUT or DELETE.
	 * @return array the request parameters
	 * @since 1.1.7
	 * @since 1.1.13 method became public
	 */
	public function getRestParams()
	{
		if($this->_restParams===null)
		{
			$result=array();
            if ($this->_isJsonEncoded()) {
                $result = \CJSON::decode($this->getRawBody());
            } else {
                if(function_exists('mb_parse_str')) {
                    mb_parse_str($this->getRawBody(), $result);
                } else {
                    parse_str($this->getRawBody(), $result);
                }
            }

			$this->_restParams=$result;
		}

		return $this->_restParams;
	}


    /**
	 * Returns the named POST parameter value.
	 * If the POST parameter does not exist, the second parameter to this method will be returned.
	 * @param string $name the POST parameter name
	 * @param mixed $defaultValue the default parameter value if the POST parameter does not exist.
	 * @return mixed the POST parameter value
	 * @see getParam
	 * @see getQuery
	 */
	public function getPost($name,$defaultValue=null)
	{
        if ($this->_isJsonEncoded()) {
            $data = \CJSON::decode($this->getRawBody());
            return isset($data[$name]) ? $data[$name] : $defaultValue;
        } else {
            return isset($_POST[$name]) ? $_POST[$name] : $defaultValue;
        }
	}

    /**
     * Check is request encoded in JSON
     * @return bool
     */
    protected function _isJsonEncoded()
    {
        if (isset($_SERVER['CONTENT_TYPE']) && strpos(strtolower($_SERVER['CONTENT_TYPE']), 'application/json') === 0) {
            return true;
        }
        return false;
    }
}




