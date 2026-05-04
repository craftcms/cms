<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

/**
 * @since 5.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Icon} instead.
 */
class Icon extends \CraftCms\Cms\Field\Icon
{
    use \craft\base\FieldEventConstants;
    use \craft\base\LegacyEventConstants;
}
