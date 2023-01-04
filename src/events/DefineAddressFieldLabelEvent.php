<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use yii\base\Event;

/**
 * DefineAddressFieldLabelEvent event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
class DefineAddressFieldLabelEvent extends Event
{
    /**
     * @var string The country code
     */
    public string $countryCode;

    /**
     * @var string $field The field to define a label for (one of the [[AddressField]] constants)
     * @see AddressField
     */
    public string $field;

    /**
     * @var string $label The field label
     */
    public string $label;
}
