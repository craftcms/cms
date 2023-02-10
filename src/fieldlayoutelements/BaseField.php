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
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Html;

/**
 * BaseField is the base class for native and custom fields that can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class BaseField extends FieldLayoutElement
{
    /**
     * @var string|null The field’s label
     */
    public ?string $label = null;

    /**
     * @var string|null The field’s instructions
     */
    public ?string $instructions = null;

    /**
     * @var string|null The field’s tip text
     */
    public ?string $tip = null;

    /**
     * @var string|null The field’s warning text
     */
    public ?string $warning = null;

    /**
     * @var bool Whether the field is required.
     */
    public bool $required = false;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        if (ArrayHelper::remove($config, 'labelHidden')) {
            $config['label'] = '__blank__';
        }

        parent::__construct($config);
    }

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
    protected function value(?ElementInterface $element = null): mixed
    {
        return $element->{$this->attribute()} ?? null;
    }

    /**
     * Returns the field’s validation errors.
     *
     * @param ElementInterface|null $element
     * @return string[]
     */
    protected function errors(?ElementInterface $element = null): array
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
        $innerHtml = '';

        $label = $this->selectorLabel();
        $indicatorHtml =
            ($this->required ? Html::tag('div', '', [
                'class' => ['fld-indicator'],
                'title' => Craft::t('app', 'This field is required'),
                'aria' => ['label' => Craft::t('app', 'This field is required')],
                'data' => ['icon' => 'asterisk'],
                'role' => 'img',
            ]) : '') .
            ($this->hasConditions() ? Html::tag('div', '', [
                'class' => ['fld-indicator'],
                'title' => Craft::t('app', 'This field is conditional'),
                'aria' => ['label' => Craft::t('app', 'This field is conditional')],
                'data' => ['icon' => 'condition'],
                'role' => 'img',
            ]) : '');

        if ($label !== null) {
            $label = Html::encode($label);
            $innerHtml .= Html::tag('div',
                Html::tag('h4', $label, [
                    'title' => $label,
                ]) . $indicatorHtml, [
                    'class' => 'fld-element-label',
                ]);
        }

        $innerHtml .= Html::tag('div',
            Html::tag('div', $this->attribute(), [
                'class' => ['smalltext', 'light', 'code'],
                'title' => $this->attribute(),
            ]) . ($label === null ? $indicatorHtml : ''), [
                'class' => 'fld-attribute',
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
     * Returns the selector label.
     *
     * @since 4.0.0
     */
    protected function selectorLabel(): ?string
    {
        return $this->showLabel() ? $this->label() : null;
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
    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('_includes/forms/fld/field-settings.twig', [
            'field' => $this,
            'defaultLabel' => $this->defaultLabel(),
            'defaultInstructions' => $this->defaultInstructions(),
            'labelHidden' => !$this->showLabel(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        $inputHtml = $this->inputHtml($element, $static);
        if ($inputHtml === null) {
            return null;
        }

        $statusClass = $this->statusClass($element, $static);
        $label = $this->showLabel() ? $this->label() : null;
        $instructions = $this->instructions($element, $static);
        $tip = $this->tip($element, $static);
        $warning = $this->warning($element, $static);

        return Cp::fieldHtml($inputHtml, [
            'fieldset' => $this->useFieldset(),
            'id' => $this->id(),
            'labelId' => $this->labelId(),
            'instructionsId' => $this->instructionsId(),
            'tipId' => $this->tipId(),
            'warningId' => $this->warningId(),
            'errorsId' => $this->errorsId(),
            'statusId' => $this->statusId(),
            'fieldAttributes' => $this->containerAttributes($element, $static),
            'inputContainerAttributes' => $this->inputContainerAttributes($element, $static),
            'labelAttributes' => $this->labelAttributes($element, $static),
            'status' => $statusClass ? [$statusClass, $this->statusLabel($element, $static) ?? ucfirst($statusClass)] : null,
            'label' => $label !== null ? Html::encode($label) : null,
            'attribute' => $this->attribute(),
            'required' => !$static && $this->required,
            'instructions' => $instructions !== null ? Html::encode($instructions) : null,
            'tip' => $tip !== null ? Html::encode($tip) : null,
            'warning' => $warning !== null ? Html::encode($warning) : null,
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
     * Returns whether the element’s form HTML should use a `<fieldset>` + `<legend>` instead of a `<div>` + `<label>`.
     *
     * @return bool
     * @since 3.6.0
     */
    protected function useFieldset(): bool
    {
        return false;
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
     * Returns the `id` of the field label.
     *
     * @return string
     * @since 4.1.0
     */
    protected function labelId(): string
    {
        return sprintf('%s-label', $this->id());
    }

    /**
     * Returns the `id` of the field instructions.
     *
     * @return string
     * @since 3.7.24
     */
    protected function instructionsId(): string
    {
        return sprintf('%s-instructions', $this->id());
    }

    /**
     * Returns the `id` of the field tip.
     *
     * @return string
     * @since 3.7.24
     */
    protected function tipId(): string
    {
        return sprintf('%s-tip', $this->id());
    }

    /**
     * Returns the `id` of the field warning.
     *
     * @return string
     * @since 3.7.24
     */
    protected function warningId(): string
    {
        return sprintf('%s-warning', $this->id());
    }

    /**
     * Returns the `id` of the field errors.
     *
     * @return string
     * @since 3.7.24
     */
    protected function errorsId(): string
    {
        return sprintf('%s-errors', $this->id());
    }

    /**
     * Returns the `id` if the field status message.
     *
     * @return string
     * @since 3.7.29
     */
    protected function statusId(): string
    {
        return sprintf('%s-status', $this->id());
    }

    /**
     * Returns the `aria-describedby` attribute value that should be set on the focusable input(s).
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return string|null
     * @see inputHtml()
     * @since 3.7.24
     */
    protected function describedBy(ElementInterface $element = null, bool $static = false): ?string
    {
        $ids = array_filter([
            (!$static && $this->errors($element)) ? $this->errorsId() : null,
            $this->statusClass($element, $static) ? $this->statusId() : null,
            $this->instructions($element, $static) ? $this->instructionsId() : null,
            $this->tip($element, $static) ? $this->tipId() : null,
            $this->warning($element, $static) ? $this->warningId() : null,
        ]);

        return $ids ? implode(' ', $ids) : null;
    }

    /**
     * Returns input container HTML attributes.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return array
     */
    protected function inputContainerAttributes(?ElementInterface $element = null, bool $static = false): array
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
    protected function labelAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        return [];
    }

    /**
     * Returns the field’s label.
     *
     * @return string|null
     */
    public function label(): ?string
    {
        if (isset($this->label) && $this->label !== '' && $this->label !== '__blank__') {
            return Craft::t('site', $this->label);
        }
        return $this->defaultLabel();
    }

    /**
     * Returns the field’s default label, which will be used if [[label]] is null.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return string|null
     */
    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return null;
    }

    /**
     * Returns whether the label should be shown in form inputs.
     *
     * @return bool
     * @since 3.5.6
     */
    protected function showLabel(): bool
    {
        return $this->label !== '__blank__';
    }

    /**
     * Returns the field’s status class.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return string|null
     */
    protected function statusClass(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($element && ($status = $element->getAttributeStatus($this->attribute()))) {
            return $status[0];
        }
        return null;
    }

    /**
     * Returns the field’s status label.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return string|null
     */
    protected function statusLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($element && ($status = $element->getAttributeStatus($this->attribute()))) {
            return $status[1];
        }
        return null;
    }

    /**
     * Returns the field’s instructions.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return string|null
     * @since 3.7.24
     */
    protected function instructions(ElementInterface $element = null, bool $static = false): ?string
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
    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
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
    abstract protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string;

    /**
     * Returns the field’s tip text.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return string|null
     */
    protected function tip(?ElementInterface $element = null, bool $static = false): ?string
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
    protected function warning(?ElementInterface $element = null, bool $static = false): ?string
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
    protected function orientation(?ElementInterface $element = null, bool $static = false): string
    {
        // If there’s only one site, go with its language
        if (!Craft::$app->getIsMultiSite()) {
            // Only one site so use its language
            $locale = Craft::$app->getSites()->getPrimarySite()->getLocale();
        } elseif (!$element || !$this->translatable($element, $static)) {
            // Not translatable, so use the user’s language
            $locale = Craft::$app->getLocale();
        } else {
            // Use the site’s language
            $locale = $element->getSite()->getLocale();
        }

        return $locale->getOrientation();
    }

    /**
     * Returns whether the field is translatable.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return bool
     */
    protected function translatable(?ElementInterface $element = null, bool $static = false): bool
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
    protected function translationDescription(?ElementInterface $element = null, bool $static = false): ?string
    {
        return null;
    }
}
