<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

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
     * @var \craft\models\Section|null The section model associated with the event.
     */
    public $section;

    /**
     * @var bool Whether the section is brand new
     */
    public $isNew = false;
}
