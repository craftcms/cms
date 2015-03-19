<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

/**
 * InvalidComponentTrait implements the common methods and properties for invalid component classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
trait InvalidComponentTrait
{
	// Static
	// =========================================================================

	// Properties
	// =========================================================================

	/**
	 * @var string The exception message that explains why the component class was invalid
	 */
	public $errorMessage;

	/**
	 * @var mixed The custom settings associated with the component, if it is savable
	 */
	public $settings;

	/**
	 * @var string|Component The expected component class name.
	 */
	private $_type;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param string|array $config       The component’s class name, or its config, with a `type` value and optionally a `settings` value
	 * @param string       $errorMessage The message explaining why an InvalidField object is getting created rather than the expected component type
	 */
	public function __construct($config = [], $errorMessage = null)
	{
		if (is_string($config))
		{
			$this->_type = $config;
			$config = [];
		}
		else
		{
			$this->_type = $config['type'];
		}

		$config['errorMessage'] = $errorMessage;

		parent::__construct($config);
	}

	/**
	 * Returns the expected component class name.
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->_type;
	}
}
