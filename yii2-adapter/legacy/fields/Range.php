<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

/**
 * @since 5.5.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Range} instead.
 */
class Range extends \CraftCms\Cms\Field\Range
{
    use \craft\base\FieldEventConstants;
    use \craft\base\LegacyEventConstants;
}
