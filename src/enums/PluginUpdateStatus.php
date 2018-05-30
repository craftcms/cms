<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\enums;

/**
 * The PluginUpdateStatus class is an abstract class that defines the different plugin version update status
 * states available in Craft.
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class PluginUpdateStatus
{
    // Constants
    // =========================================================================

    const UpToDate = 'UpToDate';
    const UpdateAvailable = 'UpdateAvailable';
    const Deleted = 'Deleted';
    const Unknown = 'Unknown';
}
