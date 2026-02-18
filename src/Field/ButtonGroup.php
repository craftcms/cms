<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use craft\base\ElementInterface;
use craft\helpers\Cp;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Field\Data\SingleOptionFieldData;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use Override;

use function CraftCms\Cms\t;

/**
 * RadioButtons represents a Radio Buttons field.
 */
final class ButtonGroup extends BaseOptionsField implements SortableFieldInterface
{
    protected static bool $optionIcons = true;

    #[Override]
    public static function displayName(): string
    {
        return t('Button Group');
    }

    #[Override]
    public static function icon(): string
    {
        return 'hand-pointer';
    }

    /**
     * @var bool Whether buttons should only show their icons, hiding their text labels
     */
    public bool $iconsOnly = false;

    #[Override]
    public function getSettingsHtml(): string
    {
        return parent::getSettingsHtml().
            Cp::lightswitchFieldHtml([
                'label' => t('Icons only'),
                'instructions' => t('Whether buttons should only show their icons, hiding their text labels.'),
                'name' => 'iconsOnly',
                'on' => $this->iconsOnly,
            ]);
    }

    #[Override]
    public function useFieldset(): bool
    {
        return true;
    }

    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->_inputHtml($value, $element, false);
    }

    #[Override]
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return $this->_inputHtml($value, $element, true);
    }

    private function _inputHtml(SingleOptionFieldData $value, ?ElementInterface $element, bool $static): string
    {
        if (! $value->valid) {
            DeltaRegistry::setInitialValue($this->handle, null);
        }

        $id = $this->getInputId();
        $options = $this->translatedOptions(true, $value, $element);

        if ($this->iconsOnly) {
            foreach ($options as &$option) {
                if (! empty($option['icon']) || ($option['icon'] ?? null) === '0') {
                    $option['attributes']['title'] = $option['attributes']['aria']['label'] = $option['label'];
                    unset($option['label']);
                }
            }
        }

        return Cp::buttonGroupHtml([
            'id' => $id,
            'name' => $this->handle,
            'static' => $static,
            'options' => $options,
            'value' => $this->encodeValue($value),
        ]);
    }
}
