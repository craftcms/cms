<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

/**
 * @since 4.6.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Country} instead.
 */
class Country extends \CraftCms\Cms\Field\Country
{
    use \craft\base\FieldEventConstants;
    use \craft\base\LegacyEventConstants;
}
