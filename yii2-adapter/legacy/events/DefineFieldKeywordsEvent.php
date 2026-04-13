<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * DefineFieldKeywordsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class DefineFieldKeywordsEvent extends Event
{
    /**
     * @var mixed The field’s value
     */
    public mixed $value = null;

    /**
     * @var ElementInterface $element The element
     */
    public ElementInterface $element;

    /**
     * @var string $keywords
     */
    public string $keywords = '';
}
