<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * DefineAttributeKeywordsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class DefineAttributeKeywordsEvent extends Event
{
    /**
     * @var string $attribute The element attribute
     */
    public string $attribute;

    /**
     * @var string $keywords
     */
    public string $keywords = '';
}
