<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\enums;

/**
 * The LicenseKeyStatus class is an abstract class that defines all of the license key status states that are available
 * in Craft.
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class LicenseKeyStatus
{
    public const Valid = 'valid';
    /**
     * @since 3.6.0
     */
    public const Trial = 'trial';
    public const Invalid = 'invalid';
    public const Mismatched = 'mismatched';
    public const Astray = 'astray';
    public const Unknown = 'unknown';
}
