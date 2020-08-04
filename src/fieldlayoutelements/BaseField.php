<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldLayoutElement;
use craft\helpers\Html;

/**
 * BaseField is the base class for custom and standard fields that can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class BaseField extends FieldLayoutElement
{
    /**
     * @var string|null The field’s label
     */
    public $label;

    /**
     * @var string|null The field’s instructions
     */
    public $instructions;

    /**
     * @var string|null The field’s tip text
     */
    public $tip;

    /**
     * @var string|null The field’s warning text
     */
    public $warning;

    /**
     * @var bool Whether the field is required.
     */
    public $required = false;

    /**
     * Returns the element attribute this field is for.
     *
     * @return string
     */
    abstract public function attribute(): string;

    /**
     * Returns the field’s value.
     *
     * @param ElementInterface|null $element
     * @return mixed
     */
    protected function value(ElementInterface $element = null)
    {
        return $element->{$this->attribute()} ?? null;
    }

    /**
     * Returns the field’s validation errors.
     *
     * @param ElementInterface|null $element
     * @return string[]
     */
    protected function errors(ElementInterface $element = null): array
    {
        if (!$element) {
            return [];
        }
        return $element->getErrors($this->attribute());
    }

    /**
     * Returns whether the field *must* be present within the layout.
     *
     * @return bool
     */
    public function mandatory(): bool
    {
        return false;
    }

    /**
     * Returns whether the field can optionally be marked as required.
     *
     * @return bool
     */
    public function requirable(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function selectorHtml(): string
    {
        return Html::tag('div', $this->selectorInnerHtml(), $this->selectorAttributes());
    }

    /**
     * Returns the selector’s inner HTML.
     *
     * @return string
     */
    protected function selectorInnerHtml(): string
    {
        $innerHtml =
            Html::tag('h4', $this->label(), [
                'class' => 'fld-element-label',
                'title' => $this->label(),
            ]) .
            Html::tag('div', $this->attribute(), [
                'class' => ['smalltext', 'light', 'code'],
                'title' => $this->attribute(),
            ]);

        return Html::tag('div', $innerHtml, [
            'class' => ['field-name'],
        ]);
    }

    /**
     * Returns HTML attributes that should be added to the selector container.
     *
     * @return array
     */
    protected function selectorAttributes(): array
    {
        return [
            'class' => 'fld-field',
            'data' => [
                'attribute' => $this->attribute(),
                'mandatory' => $this->mandatory(),
                'requirable' => $this->requirable(),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function hasCustomWidth(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function settingsHtml()
    {
        $view = Craft::$app->getView();
        return
            $view->renderTemplateMacro('_includes/forms', 'textField', [
                [
                    'label' => Craft::t('app', 'Label'),
                    'id' => 'label',
                    'name' => 'label',
                    'value' => $this->label,
                    'placeholder' => $this->defaultLabel(),
                ],
            ]) .
            $view->renderTemplateMacro('_includes/forms', 'textareaField', [
                [
                    'label' => Craft::t('app', 'Instructions'),
                    'class' => 'nicetext',
                    'id' => 'instructions',
                    'name' => 'instructions',
                    'value' => $this->instructions,
                    'placeholder' => $this->defaultInstructions(),
                ],
            ]);
    }

    /**
     * @inheritdoc
     */
    public function formHtml(ElementInterface $element = null, bool $static = false)
    {
        $inputHtml = $this->inputHtml($element, $static);
        if ($inputHtml === null) {
            return null;
        }

        $statusClass = $this->statusClass($element, $static);

        return Craft::$app->getView()->renderTemplate('_includes/forms/field', [
            'id' => $this->id(),
            'fieldAttributes' => $this->containerAttributes($element, $static),
            'inputAttributes' => $this->inputContainerAttributes($element, $static),
            'labelAttributes' => $this->labelAttributes($element, $static),
            'status' => $statusClass ? [$statusClass, $this->statusLabel($element, $static) ?? ucfirst($statusClass)] : null,
            'label' => $this->label ? Craft::t('site', $this->label) : $this->defaultLabel($element, $static),
            'altLabel' => Html::tag('code', $this->attribute()),
            'required' => !$static && $this->required,
            'instructions' => $this->_instructions($element, $static),
            'input' => $inputHtml,
            'tip' => $this->tip($element, $static),
            'warning' => $this->warning($element, $static),
            'orientation' => $this->orientation($element, $static),
            'translatable' => $this->translatable($element, $static),
            'translationDescription' => $this->translationDescription($element, $static),
            'errors' => !$static ? $this->errors($element) : [],
        ]);
    }

    /**
     * Returns the search keywords for this layout element.
     *
     * @return string[]
     */
    public function keywords(): array
    {
        return array_filter([
            $this->label(),
            $this->defaultLabel(),
            $this->attribute(),
        ]);
    }

    /**
     * Returns the `id` of the input.
     *
     * @return string
     */
    protected function id(): string
    {
        return $this->attribute();
    }

    /**
     * Returns input container HTML attributes.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return array
     */
    protected function inputContainerAttributes(ElementInterface $element = null, bool $static = false): array
    {
        return [];
    }

    /**
     * Returns label HTML attributes.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return array
     */
    protected function labelAttributes(ElementInterface $element = null, bool $static = false): array
    {
        return [];
    }

    /**
     * Returns the field’s label.
     *
     * @return string|null
     */
    public function label()
    {
        return $this->label ?: $this->defaultLabel();
    }

    /**
     * Returns the field’s default label, which will be used if [[label]] is null.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return string|null
     */
    protected function defaultLabel(ElementInterface $element = null, bool $static = false)
    {
        return null;
    }

    /**
     * Returns the field’s status class.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return string|null
     */
    protected function statusClass(ElementInterface $element = null, bool $static = false)
    {
        return null;
    }

    /**
     * Returns the field’s status label.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return string|null
     */
    protected function statusLabel(ElementInterface $element = null, bool $static = false)
    {
        return null;
    }

    /**
     * Returns the field’s instructions.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return string|null
     */
    private function _instructions(ElementInterface $element = null, bool $static = false)
    {
        return $this->instructions ? Craft::t('site', $this->instructions) : $this->defaultInstructions($element, $static);
    }

    /**
     * Returns the field’s default instructions, which will be used if [[instructions]] is null.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return string|null
     */
    protected function defaultInstructions(ElementInterface $element = null, bool $static = false)
    {
        return null;
    }

    /**
     * Returns the field’s input HTML.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return string|null
     */
    abstract protected function inputHtml(ElementInterface $element = null, bool $static = false);

    /**
     * Returns the field’s tip text.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return string|null
     */
    protected function tip(ElementInterface $element = null, bool $static = false)
    {
        return $this->tip ? Craft::t('site', $this->tip) : null;
    }

    /**
     * Returns the field’s warning text.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return string|null
     */
    protected function warning(ElementInterface $element = null, bool $static = false)
    {
        return $this->warning ? Craft::t('site', $this->warning) : null;
    }

    /**
     * Returns the field’s orientation (`ltr` or `rtl`).
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return string
     */
    protected function orientation(ElementInterface $element = null, bool $static = false): string
    {
        return Craft::$app->getLocale()->getOrientation();
    }

    /**
     * Returns whether the field is translatable.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return bool
     */
    protected function translatable(ElementInterface $element = null, bool $static = false): bool
    {
        return false;
    }

    /**
     * Returns the descriptive text for how this field is translatable.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return string|null
     */
    protected function translationDescription(ElementInterface $element = null, bool $static = false)
    {
        return null;
    }
}
