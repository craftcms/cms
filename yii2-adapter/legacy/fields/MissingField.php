<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Yii2Adapter\Field\Concerns\LegacyBuiltInField;
use CraftCms\Yii2Adapter\Field\Contracts\LegacyField;

/**
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\MissingField} instead.
 */
class MissingField extends \CraftCms\Cms\Field\MissingField implements LegacyField
{
    use LegacyBuiltInField;

    public function settingsForm(FormContext $context = new FormContext()): ?Form
    {
        if (static::class !== self::class) {
            return $this->legacySettingsForm($context);
        }

        return parent::settingsForm($context);
    }
}
