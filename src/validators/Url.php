<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\validators;
use craft\app\helpers\StringHelper;
use yii\validators\UrlValidator;

/**
 * Class Url validator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Url extends UrlValidator
{
	// Properties
	// =========================================================================

	/**
	 * Override the $pattern regex so that a TLD is not required, and the protocol may be relative.
	 *
	 * @var string
	 */
	public $pattern = '/^(?:(?:{schemes}:)?\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)?|\/)[^\s]*$/i';

	// Public Methods
	// =========================================================================

	/**
	 * Add support for protocol-relative URLs. {@see http://paulirish.com/2010/the-protocol-relative-url/}
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function validateValue($value)
	{
		// Ignore URLs with any environment variables in them
		if (StringHelper::contains($value, '{'))
		{
			return $value;
		}

		if ($this->defaultScheme !== null && strncmp($value, '/', 1) === 0)
		{
			$this->defaultScheme = null;
		}

		return parent::validateValue($value);
	}
}
