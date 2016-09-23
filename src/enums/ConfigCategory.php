<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\enums;

/**
 * The ConfigCategory class is an abstract class that defines all of the config file options that are available in Craft.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class ConfigCategory extends BaseEnum
{
    // Constants
    // =========================================================================

    const FileCache = 'filecache';
    const General = 'general';
    const Db = 'db';
    const DbCache = 'dbcache';
    const Memcache = 'memcache';
    const ApcCache = 'apc';
}
