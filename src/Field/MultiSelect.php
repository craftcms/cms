<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Cp;
use CraftCms\Cms\Field\Data\MultiOptionsFieldData;
use Illuminate\Support\Collection;

use function CraftCms\Cms\t;

/**
 * MultiSelect represents a Multi-select field.
 */
final class MultiSelect extends BaseOptionsField
{
    /**
     * {@inheritdoc}
     */
    protected static bool $multi = true;

    /**
     * {@inheritdoc}
     */
    protected static bool $optgroups = true;

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
    #[\Override]
    public static function displayName(): string
    {
        return t('Multi-select');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function icon(): string
    {
        return 'list-check';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        /** @var MultiOptionsFieldData $value */
        if (Collection::make($value)->contains('valid', '===', false)) {
            Craft::$app->getView()->setInitialDeltaValue($this->handle, null);
        }

        return Cp::selectizeHtml([
            'id' => $this->getInputId(),
            'describedBy' => $this->describedBy,
            'class' => 'selectize',
            'name' => $this->handle,
            'values' => $this->encodeValue($value),
            'options' => $this->translatedOptions(true, $value, $element),
            'multi' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getStaticHtml(mixed $value, ?ElementInterface $element = null): string
    {
        return Cp::selectizeHtml([
            'id' => $this->getInputId(),
            'describedBy' => $this->describedBy,
            'class' => 'selectize',
            'name' => $this->handle,
            'values' => $this->encodeValue($value),
            'options' => $this->translatedOptions(true, $value, $element),
            'multi' => true,
            'disabled' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function optionsSettingLabel(): string
    {
        return t('Multi-select Options');
    }
}
