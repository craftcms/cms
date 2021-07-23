<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\models\Section;
use yii\base\Event;

/**
 * Section event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SectionEvent extends Event
{
    /**
     * @var Section The section model associated with the event.
     */
    public Section $section;

    /**
     * @var bool Whether the section is brand new
     */
    public bool $isNew = false;
}
