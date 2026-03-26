<?php

namespace craft\elements\conditions\addresses;

/**
 * Asset query condition.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Address\Conditions\AddressCondition} instead.
 */
class AddressCondition extends \CraftCms\Cms\Address\Conditions\AddressCondition
{
    use \craft\base\LegacyEventConstants;
}
