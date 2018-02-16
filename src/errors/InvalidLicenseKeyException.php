<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use yii\base\Exception;

/**
 * InvalidLicenseKeyException represents an exception caused by setting an invalid license key on a plugin.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class InvalidLicenseKeyException extends Exception
{
    /**
     * @var string|null The license key
     */
    public $licenseKey;

    /**
     * Constructor.
     *
     * @param string $licenseKey The invalid license key
     * @param string|null $message The error message
     * @param int $code The error code
     */
    public function __construct(string $licenseKey, string $message = null, int $code = 0)
    {
        $this->licenseKey = $licenseKey;

        if ($message === null) {
            $message = "The license key “{$licenseKey}” is invalid.";
        }

        parent::__construct($message, $code);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Invalid license key';
    }
}
