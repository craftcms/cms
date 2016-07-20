<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\db;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Trait NestedSetsTrait.
 *
 * @method boolean       makeRoot(boolean $runValidation = true, array $attributes = null)
 * @method boolean       prependTo(ActiveRecord $node, boolean $runValidation = true, array $attributes = null)
 * @method boolean       appendTo(ActiveRecord $node, boolean $runValidation = true, array $attributes = null)
 * @method boolean       insertBefore(ActiveRecord $node, boolean $runValidation = true, array $attributes = null)
 * @method boolean       insertAfter(ActiveRecord $node, boolean $runValidation = true, array $attributes = null)
 * @method integer|false deleteWithChildren()
 * @method ActiveQuery   parents(integer $depth = null)
 * @method ActiveQuery   children(integer $depth = null)
 * @method ActiveQuery   leaves()
 * @method ActiveQuery   prev()
 * @method ActiveQuery   next()
 * @method boolean       isRoot()
 * @method boolean       isChildOf(ActiveRecord $node)
 * @method boolean       isLeaf()
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
trait NestedSetsTrait
{
}
