<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterElementHtmlAttributesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class RegisterElementHtmlAttributesEvent extends Event
{
    /**
     * @var array List of registered HTML attributes for the element
     */
    public $htmlAttributes = [];
}
