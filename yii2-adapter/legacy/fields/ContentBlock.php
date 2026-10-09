<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use CraftCms\Yii2Adapter\Field\Concerns\LegacyBuiltInField;
use CraftCms\Yii2Adapter\Field\Contracts\LegacyField;

/**
 * @since 5.8.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\ContentBlock} instead.
 */
class ContentBlock extends \CraftCms\Cms\Field\ContentBlock implements LegacyField
{
    use LegacyBuiltInField;
}
