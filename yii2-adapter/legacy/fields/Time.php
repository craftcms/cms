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
 * @since 3.5.12
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Time} instead.
 */
class Time extends \CraftCms\Cms\Field\Time implements LegacyField
{
    use LegacyBuiltInField;
}
