<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test;


use craft\base\Model;

/**
 * class EventItem
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2.0
 */
class EventItem extends Model
{
    const TYPE_CLASS = 'class';
    const TYPE_OTHERVALUE = 'othervalue';

    /**
     * @var string
     */
    public $type;

    /**
     * @var mixed
     */
    public $desiredValue;

    /**
     * @var string
     */
    public $eventPropName;

    /**
     * @var string
     */
    public $desiredClass;
}
