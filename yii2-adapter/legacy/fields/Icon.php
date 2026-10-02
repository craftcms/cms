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
 * @since 5.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Icon} instead.
 */
class Icon extends \CraftCms\Cms\Field\Icon implements LegacyField
{
    use LegacyBuiltInField;
}
