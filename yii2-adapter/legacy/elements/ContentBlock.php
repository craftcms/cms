<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use craft\base\ElementEventConstants;

/**
 * @since 5.8.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Elements\ContentBlock} instead.
 */
class ContentBlock extends \CraftCms\Cms\Field\Elements\ContentBlock
{
    use ElementEventConstants;
}
