<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\db;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord as YiiActiveRecord;

/**
 * Trait NestedSetsTrait.
 *
 * @method bool        makeRoot(bool $runValidation = true, array $attributes = null)
 * @method bool        prependTo(YiiActiveRecord $node, bool $runValidation = true, array $attributes = null)
 * @method bool        appendTo(YiiActiveRecord $node, bool $runValidation = true, array $attributes = null)
 * @method bool        insertBefore(YiiActiveRecord $node, bool $runValidation = true, array $attributes = null)
 * @method bool        insertAfter(YiiActiveRecord $node, bool $runValidation = true, array $attributes = null)
 * @method int|false   deleteWithChildren()
 * @method ActiveQuery parents(int $depth = null)
 * @method ActiveQuery children(int $depth = null)
 * @method ActiveQuery leaves()
 * @method ActiveQuery prev()
 * @method ActiveQuery next()
 * @method bool        isRoot()
 * @method bool        isChildOf(YiiActiveRecord $node)
 * @method bool        isLeaf()
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
trait NestedSetsTrait
{
}
