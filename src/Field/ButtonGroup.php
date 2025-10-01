<?php

namespace CraftCms\Cms\Field;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Cp;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Field\Data\SingleOptionFieldData;

/**
 * RadioButtons represents a Radio Buttons field.
 *
 * @since 6.0.0
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
    public static function displayName(): string
    {
        return Craft::t('app', 'Button Group');
    }

    /**
     * {@inheritdoc}
     */
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
    public function getSettingsHtml(): string
    {
        return parent::getSettingsHtml().
            Cp::lightswitchFieldHtml([
                'label' => Craft::t('app', 'Icons only'),
                'instructions' => Craft::t('app', 'Whether buttons should only show their icons, hiding their text labels.'),
                'name' => 'iconsOnly',
                'on' => $this->iconsOnly,
            ]);
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
        return $this->_inputHtml($value, $element, false);
    }

    /**
     * {@inheritdoc}
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return $this->_inputHtml($value, $element, true);
    }

    private function _inputHtml(SingleOptionFieldData $value, ?ElementInterface $element, bool $static): string
    {
        /** @var SingleOptionFieldData $value */
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
