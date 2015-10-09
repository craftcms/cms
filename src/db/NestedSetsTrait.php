<?php
/**
 * @link      http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license
 */

namespace craft\app\db;

/**
 * Trait NestedSetsTrait.
 *
 * @method boolean             makeRoot(boolean $runValidation = true, array $attributes = null)
 * @method boolean             prependTo(\yii\db\ActiveRecord $node, boolean $runValidation = true, array $attributes = null)
 * @method boolean             appendTo(\yii\db\ActiveRecord $node, boolean $runValidation = true, array $attributes = null)
 * @method boolean             insertBefore(\yii\db\ActiveRecord $node, boolean $runValidation = true, array $attributes = null)
 * @method boolean             insertAfter(\yii\db\ActiveRecord $node, boolean $runValidation = true, array $attributes = null)
 * @method integer|false       deleteWithChildren()
 * @method \yii\db\ActiveQuery parents(integer $depth = null)
 * @method \yii\db\ActiveQuery children(integer $depth = null)
 * @method \yii\db\ActiveQuery leaves()
 * @method \yii\db\ActiveQuery prev()
 * @method \yii\db\ActiveQuery next()
 * @method boolean             isRoot()
 * @method boolean             isChildOf(\yii\db\ActiveRecord $node)
 * @method boolean             isLeaf()
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
trait NestedSetsTrait
{
}
