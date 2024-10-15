<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use craft\base\FieldInterface;

/**
 * ApplyFieldSaveEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.13.0
 */
class ApplyFieldSaveEvent extends Event
{
    /**
     * @var FieldInterface|null The field associated with this event, if it already exists
     * in the database or in memory.
     */
    public ?FieldInterface $field;

    /**
     * @var array New field config data that is about to be applied.
     */
    public array $config;
}
