<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\models\Address;
use yii\base\Event;

/**
 * AddressEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AddressEvent extends Event
{
    /**
     * @var Address The address associated with the event.
     */
    public Address $address;

    /**
     * @var bool Whether the address is brand new
     */
    public bool $isNew = false;
}
