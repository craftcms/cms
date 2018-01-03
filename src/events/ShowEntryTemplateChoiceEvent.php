<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * GetRequestRouteEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ShowEntryTemplateChoiceEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var string The replacement template, if triggered event sets it.
     * @var string The current request.
     */
    public $template = null;
    public $request = null;
}
