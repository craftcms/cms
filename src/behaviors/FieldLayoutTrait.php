<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\behaviors;

use craft\app\base\ElementInterface;
use craft\app\models\FieldLayout;

/**
 * Field layout trait
 *
 * Documents the properties and behaviors added to objects by [[FieldLayoutBehavior]].
 *
 * @property ElementInterface|string $elementType The element type that the field layout will be associated with
 * @property string                  $idAttribute The name of the attribute on the owner class that is used to store the field layout’s ID
 *
 * @method FieldLayout getFieldLayout() Returns the owner's field layout
 * @method void setFieldLayout(FieldLayout $fieldLayout) Sets the owner's field layout
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
trait FieldLayoutTrait
{
}
