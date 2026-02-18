<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Craft;
use craft\base\ElementInterface;
use CraftCms\Cms\Field\Data\OptionData;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use Illuminate\Support\Collection;

use function CraftCms\Cms\t;

/**
 * Checkboxes represents a Checkboxes field.
 */
final class Checkboxes extends BaseOptionsField
{
    protected static bool $multi = true;

    protected static bool $allowCustomOptions = true;

    protected static bool $optionIcons = true;

    protected static bool $optionColors = true;

    #[\Override]
    public static function displayName(): string
    {
        return t('Checkboxes');
    }

    #[\Override]
    public static function icon(): string
    {
        return 'square-check';
    }

    #[\Override]
    public function useFieldset(): bool
    {
        return true;
    }

    #[\Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        if (! $this->customOptions && Collection::make($value)->contains(fn (OptionData $option) => ! $option->valid)) {
            DeltaRegistry::setInitialValue($this->handle, null);
        }

        return Craft::$app->getView()->renderTemplate('_includes/forms/checkboxGroup.twig', [
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'values' => $this->encodeValue($value),
            'options' => $this->translatedOptions(true, $value, $element),
            'allowCustomOptions' => $this->customOptions,
        ]);
    }

    #[\Override]
    protected function optionsSettingLabel(): string
    {
        return t('Checkbox Options');
    }
}
