<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use craft\models\TagGroup;

/**
 * Tag group event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class TagGroupEvent extends Event
{
    /**
     * @var TagGroup The tag group model associated with the event.
     */
    public TagGroup $tagGroup;

    /**
     * @var bool Whether the tag group is brand new
     */
    public bool $isNew = false;
}
