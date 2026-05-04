<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

/**
 * @since 3.0.0
 * @deprecated in 5.3.0
 */
class Url extends \CraftCms\Cms\Field\Link
{
    use \craft\base\FieldEventConstants;
    use \craft\base\LegacyEventConstants;
}
