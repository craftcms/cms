<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use craft\elements\MatrixBlock;
use craft\models\MatrixBlockType;
use yii\base\Event;

/**
 * Matrix block types event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.27
 */
class BlockTypesEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var MatrixBlockType[] The block types that will be available for the current field
     */
    public $blockTypes;

    /**
     * @var ElementInterface|null The element that the field is generating an input for.
     */
    public $element;

    /**
     * @var MatrixBlock[] The current value of the field.
     */
    public $value;
}
