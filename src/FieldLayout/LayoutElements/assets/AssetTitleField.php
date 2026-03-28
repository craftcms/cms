<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\assets;

use craft\base\ElementInterface;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\FieldLayout\LayoutElements\TitleField;
use InvalidArgumentException;
use Override;

class AssetTitleField extends TitleField
{
    #[Override]
    protected function translatable(?ElementInterface $element = null, bool $static = false): bool
    {
        if (! $element instanceof Asset) {
            throw new InvalidArgumentException(sprintf('%s can only be used in asset field layouts.', self::class));
        }

        return $element->getVolume()->titleTranslationMethod !== TranslationMethod::None;
    }

    protected function translationDescription(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (! $element instanceof Asset) {
            throw new InvalidArgumentException(sprintf('%s can only be used in asset field layouts.', self::class));
        }

        return $element->getVolume()->titleTranslationMethod->description();
    }
}
