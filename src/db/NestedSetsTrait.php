<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\db;

use yii\db\ActiveQuery;

/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */

/**
 * Trait NestedSetsTrait.
 *
 * @method boolean       makeRoot(boolean $runValidation = true, array $attributes = null)
 * @method boolean       prependTo(\yii\db\ActiveRecord $node, boolean $runValidation = true, array $attributes = null)
 * @method boolean       appendTo(\yii\db\ActiveRecord $node, boolean $runValidation = true, array $attributes = null)
 * @method boolean       insertBefore(\yii\db\ActiveRecord $node, boolean $runValidation = true, array $attributes = null)
 * @method boolean       insertAfter(\yii\db\ActiveRecord $node, boolean $runValidation = true, array $attributes = null)
 * @method integer|false deleteWithChildren()
 * @method ActiveQuery   parents(integer $depth = null)
 * @method ActiveQuery   children(integer $depth = null)
 * @method ActiveQuery   leaves()
 * @method ActiveQuery   prev()
 * @method ActiveQuery   next()
 * @method boolean       isRoot()
 * @method boolean       isChildOf(\yii\db\ActiveRecord $node)
 * @method boolean       isLeaf()
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
trait NestedSetsTrait
{
}
