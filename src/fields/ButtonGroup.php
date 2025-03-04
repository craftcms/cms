<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\SortableFieldInterface;
use craft\fields\data\SingleOptionFieldData;
use craft\helpers\Cp;
use craft\helpers\Html;

/**
 * RadioButtons represents a Radio Buttons field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ButtonGroup extends BaseOptionsField implements SortableFieldInterface
{
    /**
     * @inheritdoc
     */
    protected static bool $optionIcons = true;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Button Group');
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return parent::getSettingsHtml() .
            Cp::lightswitchFieldHtml([
                'label' => Craft::t('app', 'Icons only'),
                'instructions' => Craft::t('app', 'Whether buttons should only show their icons, hiding their text labels.'),
                'name' => 'iconsOnly',
                'on' => $this->iconsOnly,
            ]);
    }

    /**
     * @inheritdoc
     */
    public function useFieldset(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->_inputHtml($value, $element, false);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return $this->_inputHtml($value, $element, true);
    }

    private function _inputHtml(SingleOptionFieldData $value, ?ElementInterface $element, bool $static): string
    {
        /** @var SingleOptionFieldData $value */
        if (!$value->valid) {
            Craft::$app->getView()->setInitialDeltaValue($this->handle, null);
        }

        $id = $this->getInputId();

        $html = Html::beginTag('div', ['class' => 'btngroup-container']) .
            Html::beginTag('div', [
                'id' => $id,
                'class' => ['btngroup', 'btngroup--exclusive'],
                'aria' => [
                    'labelledby' => $this->getLabelId(),
                ],
            ]);

        $value = $this->encodeValue($value);

        foreach ($this->translatedOptions(true, $value, $element) as $option) {
            $selected = $option['value'] === $value;

            if ($this->iconsOnly && !empty($option['icon'])) {
                $labelHtml = Html::tag('div', Cp::iconSvg($option['icon']), [
                    'class' => 'cp-icon',
                    'aria' => [
                        'label' => $option['label'],
                    ],
                ]);
            } else {
                $labelHtml = Html::encode($option['label']);
                if (!empty($option['icon'])) {
                    $labelHtml = Html::beginTag('div', ['class' => ['flex', 'flex-inline', 'gap-xs']]) .
                        Html::tag('div', Cp::iconSvg($option['icon']), [
                            'class' => 'cp-icon',
                        ]) .
                        Html::tag('div', $labelHtml) .
                        Html::endTag('div');
                }
            }

            $html .= Cp::buttonHtml([
                'labelHtml' => $labelHtml,
                'type' => 'button',
                'class' => array_filter([
                    $selected ? 'active' : null,
                ]),
                'disabled' => $static,
                'attributes' => [
                    'aria' => [
                        'pressed' => $selected ? 'true' : 'false',
                    ],
                    'data' => [
                        'value' => $option['value'],
                    ],
                ],
            ]);
        }

        $html .= Html::endTag('div') . // .btngroup
            Html::endTag('div') . // .btngroup-container
            Html::hiddenInput($this->handle, $value, [
                'id' => "{$id}-input",
            ]);

        $view = Craft::$app->getView();
        $view->registerJsWithVars(fn($id) => <<<JS
(() => {
  new Craft.Listbox($('#' + $id), {
    onChange: (selectedOption) => {
      $('#' + $id + '-input').val(selectedOption.data('value'));
    },
  });
})();
JS, [
            $view->namespaceInputId($id),
        ]);

        return $html;
    }
}
