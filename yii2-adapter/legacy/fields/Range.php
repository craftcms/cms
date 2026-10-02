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
 * @since 5.5.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Range} instead.
 */
class Range extends \CraftCms\Cms\Field\Range implements LegacyField
{
    use LegacyBuiltInField;
}
