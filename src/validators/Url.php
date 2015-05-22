<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\validators;

use Craft;
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
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		// Enable support for validating international domain names if the intl extension is available.
		if (Craft::$app->getI18n()->getIsIntlLoaded())
		{
			$this->enableIDN = true;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function validateValue($value)
	{
		// Parse for environment variables if it looks like the URL might have one
		if (StringHelper::contains($value, '{'))
		{
			$envValue = Craft::$app->getConfig()->parseEnvironmentString($value);

			if ($hasEnvVars = ($envValue !== $value))
			{
				$value = $envValue;
			}
		}

		// Add support for protocol-relative URLs
		if ($this->defaultScheme !== null && strncmp($value, '/', 1) === 0)
		{
			$this->defaultScheme = null;
		}

		$result = parent::validateValue($value);

		if (!empty($hasEnvVars))
		{
			// Prevent yii\validators\UrlValidator::validateAttribute() from overwriting $model->$attribute
			$this->defaultScheme = null;
		}

		return $result;
	}
}
