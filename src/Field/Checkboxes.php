<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Data\OptionData;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use Illuminate\Support\Collection;
use Override;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * Checkboxes represents a Checkboxes field.
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
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        if (! $this->customOptions && is_iterable($value) && Collection::make($value)->contains(fn (OptionData $option) => ! $option->valid)) {
            DeltaRegistry::setInitialValue($this->handle, null);
        }

        return template('_includes/forms/checkboxGroup', [
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'values' => $this->encodeValue($value),
            'options' => $this->translatedOptions(true, $value, $element),
            'allowCustomOptions' => $this->customOptions,
        ]);
    }

    #[Override]
    protected function optionsSettingLabel(): string
    {
        return t('Checkbox Options');
    }
}
