<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use craft\base\ElementInterface;
use craft\helpers\Cp;
use CraftCms\Cms\Field\Data\MultiOptionsFieldData;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use Illuminate\Support\Collection;

use function CraftCms\Cms\t;

/**
 * MultiSelect represents a Multi-select field.
 */
final class MultiSelect extends BaseOptionsField
{
    protected static bool $multi = true;

    protected static bool $optgroups = true;

    protected static bool $optionIcons = true;

    protected static bool $optionColors = true;

    #[\Override]
    public static function displayName(): string
    {
        return t('Multi-select');
    }

    #[\Override]
    public static function icon(): string
    {
        return 'list-check';
    }

    #[\Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        /** @var MultiOptionsFieldData $value */
        if (Collection::make($value)->contains('valid', '===', false)) {
            DeltaRegistry::setInitialValue($this->handle, null);
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

    #[\Override]
    protected function optionsSettingLabel(): string
    {
        return t('Multi-select Options');
    }
}
