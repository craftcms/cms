<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Ui\Enums\ChoicePresentation;
use Override;

use function CraftCms\Cms\t;

/**
 * RadioButtons represents a Radio Buttons field.
 *
 * @since 6.0.0
 */
class RadioButtons extends BaseOptionsField implements SortableFieldInterface
{
    #[Override]
    protected static bool $allowCustomOptions = true;

    #[Override]
    protected static bool $optionIcons = true;

    #[Override]
    protected static bool $optionColors = true;

    #[Override]
    public static function displayName(): string
    {
        return t('Radio Buttons');
    }

    #[Override]
    public static function icon(): string
    {
        return 'circle-dot';
    }

    #[Override]
    public function useFieldset(): bool
    {
        return true;
    }

    #[Override]
    protected function uiPresentation(): ChoicePresentation
    {
        return ChoicePresentation::Radios;
    }

    #[Override]
    protected function optionsSettingLabel(): string
    {
        return t('Radio Button Options');
    }
}
