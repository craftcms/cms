<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

/**
 * @since 5.8.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\ContentBlock} instead.
 */
class ContentBlock extends \CraftCms\Cms\Field\ContentBlock
{
    use \craft\base\FieldEventConstants;
    use \craft\base\LegacyEventConstants;
}
