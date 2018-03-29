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
 * @since 3.0
 */
abstract class LicenseKeyStatus
{
    // Constants
    // =========================================================================

    const Valid = 'valid';
    const Invalid = 'invalid';
    const Mismatched = 'mismatched';
    const Astray = 'astray';
    const Unknown = 'unknown';
}
