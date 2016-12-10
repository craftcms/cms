<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterRichTextLinkOptionsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class RegisterRichTextLinkOptionsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array The registered link options
     */
    public $linkOptions = [];
}
