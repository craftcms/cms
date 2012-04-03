<?php
namespace Blocks;

/**
 *
 */
class Tag
{
	private $_obj;
	private $_subtagCache = array();

	/**
	 * Constructor
	 *
	 * @param null $var
	 */
	function __construct($var = null)
	{
		// Set the var object
		if (is_object($var))
			$this->_obj = $var;
		else if (is_numeric($var))
			$this->_obj = new NumTag($var);
		else if (is_array($var))
			$this->_obj = new ArrayTag($var);
		else if (is_bool($var))
			$this->_obj = new BoolTag($var);
		else
			$this->_obj = new StringTag($var);
	}

	/**
	 * @param $name
	 * @param array $args
	 * @return Tag
	 */
	function __call($name, $args = array())
	{
		$cacheKey = $name;
		if ($args)
			$cacheKey .= '('.serialize($args).')';

		// Make sure we haven't called this exact subtag already
		if (!isset($this->_subtagCache[$cacheKey]))
		{
			if (method_exists($this->_obj, $name))
				$var = call_user_func_array(array($this->_obj, $name), $args);
			else
			{
				try
				{
					$var = @$this->_obj->$name;
				}
				catch (Exception $e)
				{
					$var = null;
				}
				catch (\Exception $e)
				{
					$var = null;
				}
			}

			$this->_subtagCache[$cacheKey] = TemplateHelper::getTag($var);
		}

		return $this->_subtagCache[$cacheKey];
	}

	/**
	 * @return string
	 */
	function __toString()
	{
		if (method_exists($this->_obj, '__toString'))
			return $this->_obj->__toString();
		else
			return get_class($this->_obj);
	}

	/**
	 * @return array
	 */
	function __toArray()
	{
		if (method_exists($this->_obj, '__toArray'))
			return $this->_obj->__toArray();
		else if (method_exists($this->_obj, 'getAll'))
			return $this->_obj->getAll();
		else
			return array();
	}
}
