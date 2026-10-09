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
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Date} instead.
 */
class Date extends \CraftCms\Cms\Field\Date implements LegacyField
{
    use LegacyBuiltInField;
}
