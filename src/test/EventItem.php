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
    public const TYPE_CLASS = 'class';
    public const TYPE_OTHERVALUE = 'othervalue';

    /**
     * @var string
     */
    public string $type;

    /**
     * @var mixed
     */
    public mixed $desiredValue = null;

    /**
     * @var string
     */
    public string $eventPropName;

    /**
     * @var string
     */
    public string $desiredClass;
}
