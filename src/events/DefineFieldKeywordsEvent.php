<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use yii\base\Event;

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
    public $value;

    /**
     * @var ElementInterface $element The element
     */
    public $element;

    /**
     * @var string $keywords
     */
    public $keywords = '';
}
