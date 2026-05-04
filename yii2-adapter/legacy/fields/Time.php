<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

/**
 * @since 3.5.12
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Time} instead.
 */
class Time extends \CraftCms\Cms\Field\Time
{
    use \craft\base\FieldEventConstants;
    use \craft\base\LegacyEventConstants;
}
