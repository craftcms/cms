<?php
namespace Craft;

/**
 * Class InvalidLicenseKeyException
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.errors
 * @since     1.0
 */
class InvalidLicenseKeyException extends \Twig_Error_Loader
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	public $licenseKey;

	// Public Methods
	// =========================================================================

	/**
	 * @param string $licenseKey
	 *
	 * @return InvalidLicenseKeyException
	 */
	public function __construct($licenseKey)
	{
		$this->licenseKey = $licenseKey;
		$message = "The license key “{$licenseKey}” is invalid.";

		parent::__construct($message);
	}
}
