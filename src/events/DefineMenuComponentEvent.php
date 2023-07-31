<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * DefineMenuComponentEvent is used to define the HTML for components that are added to the additional menu.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class DefineMenuComponentEvent extends Event
{
    /**
     * @var array The array of UI components HTML
     */
    public array $components = [];
}
