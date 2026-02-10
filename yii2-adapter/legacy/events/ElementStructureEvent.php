<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

/**
 * ElementStructureEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 Use {@see \CraftCms\Cms\Element\Events\BeforeMoveInStructure} or {@see \CraftCms\Cms\Element\Events\AfterMoveInStructure} instead.
 */
class ElementStructureEvent extends ModelEvent
{
    /**
     * @var int The structure ID
     */
    public int $structureId;
}
