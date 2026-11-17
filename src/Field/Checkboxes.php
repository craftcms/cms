<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Override;

use function CraftCms\Cms\t;

/**
 * Checkboxes represents a Checkboxes field.
 *
 * @since 6.0.0
 */
class Checkboxes extends BaseOptionsField
{
    #[Override]
    protected static bool $multi = true;

    #[Override]
    protected static bool $allowCustomOptions = true;

    #[Override]
    protected static bool $optionIcons = true;

    #[Override]
    protected static bool $optionColors = true;

    #[Override]
    public static function displayName(): string
    {
        return t('Checkboxes');
    }

    #[Override]
    public static function icon(): string
    {
        return 'square-check';
    }

    #[Override]
    public function useFieldset(): bool
    {
        return true;
    }

    #[Override]
    protected function optionsSettingLabel(): string
    {
        return t('Checkbox Options');
    }
}
