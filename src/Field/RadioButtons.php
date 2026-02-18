<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Craft;
use craft\base\ElementInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Field\Data\SingleOptionFieldData;
use CraftCms\Cms\Support\Facades\DeltaRegistry;

use function CraftCms\Cms\t;

/**
 * RadioButtons represents a Radio Buttons field.
 */
final class RadioButtons extends BaseOptionsField implements SortableFieldInterface
{
    protected static bool $allowCustomOptions = true;

    protected static bool $optionIcons = true;

    protected static bool $optionColors = true;

    #[\Override]
    public static function displayName(): string
    {
        return t('Radio Buttons');
    }

    #[\Override]
    public static function icon(): string
    {
        return 'circle-dot';
    }

    #[\Override]
    public function useFieldset(): bool
    {
        return true;
    }

    #[\Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        /** @var SingleOptionFieldData $value */
        if (! $value->valid && ! $this->customOptions) {
            DeltaRegistry::setInitialValue($this->handle, null);
        }

        $options = $this->translatedOptions(true, $value, $element);
        if ($this->customOptions && $value->valid) {
            // Add the custom option
            $options[] = [
                'label' => null,
                'value' => '',
                'custom' => true,
            ];
        }

        return Craft::$app->getView()->renderTemplate('_includes/forms/radioGroup.twig', [
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'value' => $this->encodeValue($value),
            'options' => $options,
        ]);
    }

    #[\Override]
    protected function optionsSettingLabel(): string
    {
        return t('Radio Button Options');
    }
}
