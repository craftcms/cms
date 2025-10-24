<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Craft;
use craft\base\ElementInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Field\Data\SingleOptionFieldData;

use function CraftCms\Cms\t;

/**
 * RadioButtons represents a Radio Buttons field.
 */
final class RadioButtons extends BaseOptionsField implements SortableFieldInterface
{
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
        return t('Radio Buttons');
    }

    /**
     * {@inheritdoc}
     */
    public static function icon(): string
    {
        return 'circle-dot';
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
        /** @var SingleOptionFieldData $value */
        if (! $value->valid && ! $this->customOptions) {
            Craft::$app->getView()->setInitialDeltaValue($this->handle, null);
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

    /**
     * {@inheritdoc}
     */
    protected function optionsSettingLabel(): string
    {
        return t('Radio Button Options');
    }
}
