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
 * @since 5.5.0
 */
class ApplyFieldSaveEvent extends Event
{
    /**
     * @var FieldInterface|null The field associated with this event, as
     * configured before the changes are applied to it (if it already exists).
     */
    public ?FieldInterface $field = null;

    /**
     * @var array New field config data that is about to be applied.
     */
    public array $config;
}
