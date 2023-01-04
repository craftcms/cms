<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * DefineAddressFieldsEvent event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
class DefineAddressFieldsEvent extends Event
{
    /**
     * @var string The country code
     */
    public string $countryCode;

    /**
     * @var string[] $fields The fields available for the country
     */
    public array $fields;
}
