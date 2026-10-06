<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Yii2Adapter\Field\Concerns\LegacyBuiltInField;
use CraftCms\Yii2Adapter\Field\Contracts\LegacyField;
use CraftCms\Yii2Adapter\Form\NestedElementFieldHtml;
use Override;
use RuntimeException;

/**
 * @since 5.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Addresses} instead.
 */
class Addresses extends \CraftCms\Cms\Field\Addresses implements LegacyField
{
    use LegacyBuiltInField;

    /**
     * @throws RuntimeException
     */
    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $control = parent::formControl(new FieldContext(
            path: ['fields', $this->handle],
            value: $value,
            element: $element,
            mode: $this->legacyInputMode,
            inline: $inline,
        ));

        return app(NestedElementFieldHtml::class)->render($control, $this->getInputId(), $this->handle, $this->legacyInputMode);
    }
}
