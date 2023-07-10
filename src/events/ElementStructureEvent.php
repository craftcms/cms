<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\records\StructureElement;

/**
 * ElementStructureEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ElementStructureEvent extends ModelEvent
{
    /**
     * @var int The structure ID
     */
    public int $structureId;

    /**
     * @var StructureElement The target structure element record
     */
    public StructureElement $targetElementRecord;
}
