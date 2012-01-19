<?php

/**
 *
 */
class bTag
{
	public $__tag__ = true;
	private $_tagCache = array();

	/**
	 * Constructor - right now this is just a wrapper for init(), but some day we may want to do extra stuff on construct
	 */
	public function __construct()
	{
		if (method_exists($this, 'init'))
		{
			$args = func_get_args();
			call_user_func_array(array($this, 'init'), $args);
		}
	}

	/**
	 * @param $tag
	 * @param array $args
	 * @return mixed
	 */
	public function _subtag($tag, $args = array())
	{
		$cacheKey = bTemplateHelper::generateTagCacheKey($tag, $args);

		if (!isset($this->_tagCache[$cacheKey]))
		{
			$ret = call_user_func_array(array($this, $tag), $args);
			$this->_tagCache[$cacheKey] = bTemplateHelper::getVarTag($ret);
		}

		return $this->_tagCache[$cacheKey];
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return '';
	}

	/**
	 * @return bool
	 */
	public function __toBool()
	{
		return false;
	}

	/**
	 * @return array
	 */
	public function __toArray()
	{
		return array($this);
	}

	/**
	 * @param $name
	 * @param $args
	 * @return Tag
	 */
	public function __call($name, $args)
	{
		return new bTag;
	}

	/**
	 * @return string
	 */
	public function classname()
	{
		return get_class($this);
	}
}
