<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * Batchable defines the common interface to be implemented by classes that
 * provide items which can be counted and accessed in slices.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 * @deprecated in Craft 6.0.0. Use [[\CraftCms\Cms\Queue\Contracts\Batchable]] instead.
 */
interface Batchable extends \CraftCms\Cms\Queue\Contracts\Batchable
{
}
