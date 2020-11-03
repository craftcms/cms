<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * Element criteria event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.16
 */
class ElementCriteriaEvent extends Event
{
    /**
     * @var array The criteria that should be used to query for elements.
     */
    public $criteria = [];
}
