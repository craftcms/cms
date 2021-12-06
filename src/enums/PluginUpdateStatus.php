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
 * @since 3.0.0
 */
abstract class PluginUpdateStatus
{
    public const UpToDate = 'UpToDate';
    public const UpdateAvailable = 'UpdateAvailable';
    public const Deleted = 'Deleted';
    public const Unknown = 'Unknown';
}
