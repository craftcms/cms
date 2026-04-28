<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

/**
 * NestedElementQueryInterface defines the common interface to be implemented by element query classes
 * which can query for nested elements.
 *
 * An implementation of this interface is provided by [[NestedElementQueryTrait]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.4.9
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Queries\Contracts\NestedElementQueryInterface} instead.
 */
interface NestedElementQueryInterface extends \CraftCms\Cms\Element\Queries\Contracts\NestedElementQueryInterface
{
}
