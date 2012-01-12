<?php

/**
 *
 */
class Tag
{
	public $__tag__ = true;
	private $_tagCache = array();

	/**
	 * Constructor - right now this is just a wrapper for init(), but some day we may want to do extra stuff on construct
	 *
	 * @access public
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
	 * @access public
	 *
	 * @param $tag
	 * @param array $args
	 *
	 * @return mixed
	 */
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

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function __toString()
	{
		return '';
	}

	/**
	 * @access public
	 *
	 * @return bool
	 */
	public function __toBool()
	{
		return false;
	}

	/**
	 * @access public
	 *
	 * @return array
	 */
	public function __toArray()
	{
		return array($this);
	}

	/**
	 * @access public
	 *
	 * @param $name
	 * @param $args
	 *
	 * @return Tag
	 */
	public function __call($name, $args)
	{
		return new Tag;
	}

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function classname()
	{
		return get_class($this);
	}
}
