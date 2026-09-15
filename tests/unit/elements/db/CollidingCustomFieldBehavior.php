<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements\db;

use craft\behaviors\CustomFieldBehavior;

/**
 * Stands in for a generated CustomFieldBehavior on an install that has a field handle which
 * collides with one of the query’s own properties.
 *
 * `where` is a reserved handle now, but fields created before it was reserved are still out there,
 * and reserved handles are only validated when a field is saved.
 *
 * @see ElementQueryTest::testCriteriaAttributesSkipQueryPropertyCollisions()
 */
class CollidingCustomFieldBehavior extends CustomFieldBehavior
{
    /**
     * @var mixed Collides with the query's own `where` property.
     */
    public mixed $where = null;

    /**
     * @var mixed A handle that doesn't collide with anything.
     */
    public mixed $myFieldHandle = null;
}
