<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Yii2Adapter\Field\Concerns\LegacyBuiltInField;
use CraftCms\Yii2Adapter\Field\Contracts\LegacyField;

/**
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\MissingField} instead.
 */
class MissingField extends \CraftCms\Cms\Field\MissingField implements LegacyField
{
    use LegacyBuiltInField;

    public function settingsUi(UiContext $context = new UiContext()): ?Ui
    {
        if (static::class !== self::class) {
            return $this->legacySettingsUi($context);
        }

        return parent::settingsUi($context);
    }
}
