<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * DefineAddressSubdivisionsEvent event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.0
 */
class DefineAddressSubdivisionsEvent extends Event
{
    /**
     * @var array The field's parents; always in order of: countryCode, administrativeArea, locality
     */
    public array $parents;

    /**
     * @var string[] $subdivisions The subdivisions
     */
    public array $subdivisions;
}
