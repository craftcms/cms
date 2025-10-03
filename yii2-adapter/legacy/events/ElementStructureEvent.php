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
 * @deprecated in 4.5.0. [[\craft\services\Structures::EVENT_BEFORE_INSERT_ELEMENT]],
 * [[\craft\services\Structures::EVENT_AFTER_INSERT_ELEMENT|EVENT_AFTER_INSERT_ELEMENT]],
 * [[\craft\services\Structures::EVENT_BEFORE_MOVE_ELEMENT|EVENT_BEFORE_MOVE_ELEMENT]] and
 * [[\craft\services\Structures::EVENT_AFTER_MOVE_ELEMENT|EVENT_AFTER_MOVE_ELEMENT]] should be used instead.
 */
class ElementStructureEvent extends ModelEvent
{
    /**
     * @var int The structure ID
     */
    public int $structureId;
}
