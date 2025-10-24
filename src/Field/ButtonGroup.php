<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Cp;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Field\Data\SingleOptionFieldData;

use function CraftCms\Cms\t;

/**
 * RadioButtons represents a Radio Buttons field.
 */
final class ButtonGroup extends BaseOptionsField implements SortableFieldInterface
{
    /**
     * {@inheritdoc}
     */
    protected static bool $optionIcons = true;

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function displayName(): string
    {
        return t('Button Group');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function icon(): string
    {
        return 'hand-pointer';
    }

    /**
     * @var bool Whether buttons should only show their icons, hiding their text labels
     */
    public bool $iconsOnly = false;

    /**
     * {@inheritdoc}
     */
    #[\Override]
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

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function useFieldset(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->_inputHtml($value, $element, false);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return $this->_inputHtml($value, $element, true);
    }

    private function _inputHtml(SingleOptionFieldData $value, ?ElementInterface $element, bool $static): string
    {
        if (! $value->valid) {
            Craft::$app->getView()->setInitialDeltaValue($this->handle, null);
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
            'options' => $options,
            'value' => $this->encodeValue($value),
        ]);
    }
}
