<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * DefineAddressCountriesEvent event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.5.0
 */
class DefineAddressCountriesEvent extends Event
{
    /**
     * @var string $locale
     */
    public string $locale;

    /**
     * @var array list of countries keyed by their country code.
     */
    public array $countries;
}
