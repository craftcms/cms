<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\db;

use yii\db\ActiveQuery;

/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */

/**
 * Trait NestedSetsTrait.
 *
 * @method bool       makeRoot(bool $runValidation = true, array $attributes = null)
 * @method bool       prependTo(\yii\db\ActiveRecord $node, bool $runValidation = true, array $attributes = null)
 * @method bool       appendTo(\yii\db\ActiveRecord $node, bool $runValidation = true, array $attributes = null)
 * @method bool       insertBefore(\yii\db\ActiveRecord $node, bool $runValidation = true, array $attributes = null)
 * @method bool       insertAfter(\yii\db\ActiveRecord $node, bool $runValidation = true, array $attributes = null)
 * @method int|false deleteWithChildren()
 * @method ActiveQuery   parents(int $depth = null)
 * @method ActiveQuery   children(int $depth = null)
 * @method ActiveQuery   leaves()
 * @method ActiveQuery   prev()
 * @method ActiveQuery   next()
 * @method bool       isRoot()
 * @method bool       isChildOf(\yii\db\ActiveRecord $node)
 * @method bool       isLeaf()
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
trait NestedSetsTrait
{
}
