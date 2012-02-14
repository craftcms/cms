<?php
namespace Blocks;

/**
 *
 */
abstract class BaseComponent extends \CApplicationComponent
{
	protected $classPrefix;
	protected $classSuffix;

	private $_class;

	/**
	 * Get the class name, sans the namespace, prefix and suffix
	 */
	public function getClass()
	{
		if (!isset($this->_class))
		{
			$this->_class = get_class($this);

			// Chop off the namespace
			$namespaceLength = strlen(__NAMESPACE__) + 1;
			if (substr($this->_class, 0, $namespaceLength) == __NAMESPACE__.'\\')
				$this->_class = substr($this->_class, $namespaceLength);

			// Chop off the class prefix
			if (isset($this->classPrefix))
			{
				$prefixLength = strlen($this->classPrefix);
				if (substr($this->_class, 0, $prefixLength) == $this->classPrefix)
					$this->_class = substr($this->_class, $prefixLength);
			}

			// Chop off the class suffix
			if (isset($this->classSuffix))
			{
				$suffixLength = strlen($this->classSuffix);
				if (substr($this->_class, -$suffixLength) == $this->classSuffix)
					$this->_class = substr($this->_class, 0, -$suffixLength);
			}
		}

		return $this->_class;
	}

}
