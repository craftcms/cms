<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementActionInterface;
use craft\elements\db\ElementQueryInterface;
use yii\base\Event;

/**
 * Render Element event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class RenderElementsEvent extends Event
{
    /**
     * @var string the template to render
     */
    public $template;

    /**
     * @var array the variables passed to the template
     */
    public $variables;
}
