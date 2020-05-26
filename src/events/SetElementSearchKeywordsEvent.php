<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * SetElementSearchKeywordsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.21
 */
class SetElementSearchKeywordsEvent extends Event
{
    /**
     * @var string The attribute that should be indexed for the element.
     */
    public $attribute;

    /**
     * @var mixed The attribute's keywords that should be indexed for the element.
     */
    public $keywords;
}
