<?php

class Tag
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

	public function _subtag($tag, $args = array())
	{
		$cacheKey = TemplateHelper::generateTagCacheKey($tag, $args);

		if (!isset($this->_tagCache[$cacheKey]))
		{
			$ret = call_user_func_array(array($this, $tag), $args);
			$this->_tagCache[$cacheKey] = TemplateHelper::getVarTag($ret);
		}

		return $this->_tagCache[$cacheKey];
	}

	public function __toString()
	{
		return '';
	}

	public function __toBool()
	{
		return false;
	}

	public function __toArray()
	{
		return array($this);
	}

	public function __call($name, $args)
	{
		return new Tag;
	}

	public function classname()
	{
		return get_class($this);
	}
}
