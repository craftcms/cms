<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\enums;

/**
 * LicenseKeyStatus defines all possible license key statuses for Craft and plugins.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
enum LicenseKeyStatus: string
{
    case Valid = 'valid';
    case Trial = 'trial';
    case Invalid = 'invalid';
    case Mismatched = 'mismatched';
    case Astray = 'astray';
    case Unknown = 'unknown';
}
