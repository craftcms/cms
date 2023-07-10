<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\records\StructureElement;

/**
 * Move element event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class MoveElementEvent extends ElementEvent
{
    /**
     * @var int The ID of the structure the element is being moved within.
     */
    public int $structureId;

    /**
     * @var StructureElement The target structure element record
     */
    public StructureElement $targetElementRecord;
}
