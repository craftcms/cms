<?php

namespace CraftCms\Cms\Field;

use Craft;
use craft\base\ElementInterface;
use CraftCms\Cms\Field\Data\OptionData;
use Illuminate\Support\Collection;

/**
 * Checkboxes represents a Checkboxes field.
 *
 * @since 6.0.0
 */
final class Checkboxes extends BaseOptionsField
{
    /**
     * {@inheritdoc}
     */
    protected static bool $multi = true;

    /**
     * {@inheritdoc}
     */
    protected static bool $allowCustomOptions = true;

    /**
     * {@inheritdoc}
     */
    protected static bool $optionIcons = true;

    /**
     * {@inheritdoc}
     */
    protected static bool $optionColors = true;

    /**
     * {@inheritdoc}
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Checkboxes');
    }

    /**
     * {@inheritdoc}
     */
    public static function icon(): string
    {
        return 'square-check';
    }

    /**
     * {@inheritdoc}
     */
    public function useFieldset(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        if (! $this->customOptions && Collection::make($value)->contains(fn (OptionData $option) => ! $option->valid)) {
            Craft::$app->getView()->setInitialDeltaValue($this->handle, null);
        }

        return Craft::$app->getView()->renderTemplate('_includes/forms/checkboxGroup.twig', [
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'values' => $this->encodeValue($value),
            'options' => $this->translatedOptions(true, $value, $element),
            'allowCustomOptions' => $this->customOptions,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function optionsSettingLabel(): string
    {
        return Craft::t('app', 'Checkbox Options');
    }
}
