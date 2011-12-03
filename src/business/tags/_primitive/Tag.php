<?php

class Tag
{
	public $__tag__ = true;
	private $_tagCache = array();

	/**
	 * Returns whether a variable is a tag or not
	 */
	public static function _isTag($var)
	{
		return (is_object($var) && isset($var->__tag__));
	}

	/**
	 * Returns the appropriate tag for a variable
	 * @param mixed $var The variable
	 */
	public static function _getVarTag($var = '')
	{
		if (self::_isTag($var))
			return $var;

		if (is_int($var) || is_float($var))
			return new NumTag($var);

		if (is_array($var))
			return new ArrayTag($var);

		if (is_bool($var))
			return new BoolTag($var);

		if (is_object($var))
		{
			if (method_exists($var, '__toString'))
				$str = $var->__toString();
			else
				$str = get_class($var);

			return new StringTag($str);
		}

		return new StringTag($var);
	}

	public function _tag($tag, $args = array())
	{
		$cacheKey = $this->_getTagCacheKey($tag, $args);
		if (!isset($this->_tagCache[$cacheKey]))
		{
			$ret = call_user_func_array(array($this, $tag), $args);
			$this->_tagCache[$cacheKey] = self::_getVarTag($ret);
		}

		return $this->_tagCache[$cacheKey];
	}

	private function _getTagCacheKey($tag, $args)
	{
		$cacheKey = $tag;
		if ($args) $cacheKey .= '('.serialize($args).')';
		return $cacheKey;
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
