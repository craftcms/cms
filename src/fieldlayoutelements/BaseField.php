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
use craft\events\DefineFieldActionsEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;

/**
 * BaseField is the base class for native and custom fields that can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class BaseField extends FieldLayoutElement
{
    /**
     * @event DefineFieldActionsEvent The event that is triggered when defining action menu items.
     * @see actionMenuItems()
     * @since 5.9.0
     */
    public const EVENT_DEFINE_ACTION_MENU_ITEMS = 'defineActionMenuItems';

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
     * @var bool Whether this field should be used to define element thumbnails.
     * @since 5.0.0
     * @deprecated in 5.9.0
     */
    public bool $providesThumbs = false;

    /**
     * @var bool Whether this field’s contents should be included in element cards.
     * @since 5.0.0
     * @deprecated in 5.9.0
     */
    public bool $includeInCards = false;

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
     * @inheritdoc
     */
    public function fields(): array
    {
        $fields = parent::fields();
        unset($fields['includeInCards'], $fields['providesThumbs']);
        return $fields;
    }

    /**
     * Returns the element attribute this field is for.
     *
     * @return string
     */
    abstract public function attribute(): string;

    /**
     * Returns the key for this field.
     *
     * @return string
     * @since 5.9.0
     */
    public function key(): string
    {
        $uid = $this->uid ?? '{uid}';

        return "layoutElement:$uid";
    }

    /**
     * Returns whether the attribute should be shown for admin users with “Show field handles in edit forms” enabled.
     *
     * @return bool
     * @since 4.5.4
     */
    public function showAttribute(): bool
    {
        return false;
    }

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
     * Returns whether the field can be chosen as elements’ thumbnail provider.
     *
     * @return bool
     * @since 5.0.0
     */
    public function thumbable(): bool
    {
        return false;
    }

    /**
     * Returns whether the field can be included in element cards.
     *
     * @return bool
     * @since 5.0.0
     */
    public function previewable(): bool
    {
        return false;
    }

    /**
     * Returns the card preview options supplied by this field.
     *
     * @return array|null
     * @since 5.9.0
     */
    public function getPreviewOptions(): ?array
    {
        if (!$this->previewable()) {
            return null;
        }

        return [
            [
                'label' => $this->selectorLabel() ?? $this->attribute(),
                'value' => 'layoutElement:{uid}',
            ],
        ];
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
        $icon = $this->selectorIcon();

        $indicatorHtml = implode('', array_map(fn(array $indicator) => Html::tag('div', Cp::iconSvg($indicator['icon'], altText: $indicator['label']), [
            'class' => array_filter(['cp-icon', 'puny', $indicator['iconColor'] ?? null]),
            'title' => $indicator['label'],
        ]), $this->selectorIndicators()));

        if ($label !== null) {
            $label = Html::encode($label);
            $innerHtml .= Html::tag('div',
                Html::tag('h4', $label, [
                    'title' => $label,
                ]), [
                    'class' => 'fld-element-label',
                ]);
        }

        $innerHtml .=
            Html::beginTag('div', [
                'class' => 'fld-attribute',
            ]) .
            Html::tag('div', $this->attribute(), [
                'class' => ['smalltext', 'light', 'code', 'fld-attribute-label'],
                'title' => $this->attribute(),
            ]) .
            Html::endTag('div'); // .fld-attribute

        if ($indicatorHtml) {
            $innerHtml .= Html::tag('div', $indicatorHtml, [
                'class' => ['fld-field-indicators', 'flex', 'flex-nowrap', 'gap-xs'],
            ]);
        }

        $html = Html::tag('div', $innerHtml, [
            'class' => ['field-name'],
        ]);

        if ($icon) {
            $html = Html::tag('div', Cp::iconSvg($icon), [
                'class' => ['cp-icon', 'medium'],
            ]) . $html;
        }

        return $html;
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
                'thumbable' => $this->thumbable(),
                'preview-options' => $this->getPreviewOptions(),
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
     * Returns the selector’s SVG icon.
     *
     * The returned icon can be a system icon’s name (e.g. `'whiskey-glass-ice'`),
     * the path to an SVG file, or raw SVG markup.
     *
     * System icons can be found in `src/icons/solid/`.
     *
     * @return string|null
     * @since 5.0.0
     */
    protected function selectorIcon(): ?string
    {
        return null;
    }

    /**
     * Returns the indicators that should be shown within the selector.
     *
     * @since 5.0.0
     */
    protected function selectorIndicators(): array
    {
        $indicators = [];

        if ($this->requirable() && $this->required) {
            $indicators[] = [
                'label' => Craft::t('app', 'This field is required'),
                'icon' => 'asterisk',
                'iconColor' => 'rose',
            ];
        }

        if (isset($this->tip)) {
            $indicators[] = [
                'label' => Craft::t('app', 'This field has a tip'),
                'icon' => 'lightbulb',
                'iconColor' => 'sky',
            ];
        }

        if (isset($this->warning)) {
            $indicators[] = [
                'label' => Craft::t('app', 'This field has a warning'),
                'icon' => 'alert',
                'iconColor' => 'amber',
            ];
        }

        if ($this->hasConditions()) {
            $indicators[] = [
                'label' => Craft::t('app', 'This field is conditional'),
                'icon' => 'diamond',
                'iconColor' => 'orange',
            ];
        }

        return $indicators;
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
    public function hasSettings()
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

        $showStatus = $this->showStatus();
        $statusClass = $showStatus ? $this->statusClass($element, $static) : null;
        $label = $this->showLabel() ? $this->label() : null;
        $instructions = $this->instructions($element, $static);
        $tip = $this->tip($element, $static);
        $warning = $this->warning($element, $static);
        $translatable = $this->translatable($element, $static);
        $actionMenuItems = $this->actionMenuItems($element, $static);

        if ($this->hasEventHandlers(self::EVENT_DEFINE_ACTION_MENU_ITEMS)) {
            $event = new DefineFieldActionsEvent([
                'element' => $element,
                'static' => $static,
                'items' => $actionMenuItems,
            ]);
            $this->trigger(self::EVENT_DEFINE_ACTION_MENU_ITEMS, $event);
            $actionMenuItems = $event->items;
        }

        if (
            $this->uid &&
            $element?->id &&
            !$static &&
            $this->isCrossSiteCopyable($element) &&
            $this->translatable($element, $static) &&
            $element->getIsCrossSiteCopyable()
        ) {
            // prepare namespace for the purpose of copying
            $namespace = Craft::$app->getView()->getNamespace();

            $actionMenuItems = array_filter([
                [
                    'icon' => 'clone',
                    'label' => Craft::t('app', 'Copy value from site…'),
                    'attributes' => [
                        'data' => [
                            'cross-site-copy' => true,
                            'element-id' => $element->id,
                            'layout-element' => $this->uid,
                            'label' => $label,
                            'namespace' => ($namespace && $namespace !== 'fields')
                                ? StringHelper::removeRight($namespace, '[fields]')
                                : null,
                        ],
                    ],
                ],
                !empty($actionMenuItems) ? ['type' => 'hr'] : null,
                ...$actionMenuItems,
            ]);
        }

        return Cp::fieldHtml($inputHtml, [
            'fieldClass' => array_keys(array_filter([
                'no-status' => !$showStatus,
            ])),
            'fieldset' => $this->useFieldset(),
            'id' => $this->id(),
            'labelId' => $this->labelId(),
            'instructionsId' => $this->instructionsId(),
            'tipId' => $this->tipId(),
            'warningId' => $this->warningId(),
            'errorsId' => $this->errorsId(),
            'statusId' => $showStatus ? $this->statusId() : null,
            'fieldAttributes' => $this->containerAttributes($element, $static),
            'inputContainerAttributes' => $this->inputContainerAttributes($element, $static),
            'labelAttributes' => $this->labelAttributes($element, $static),
            'status' => $statusClass ? [$statusClass, $this->statusLabel($element, $static) ?? ucfirst($statusClass)] : null,
            'static' => $static,
            'label' => $label !== null ? Html::encode($label) : null,
            'attribute' => $this->attribute(),
            'showAttribute' => $this->showAttribute(),
            'required' => !$static && $this->required,
            'instructions' => $instructions !== null ? Html::encode($instructions) : null,
            'tip' => $tip !== null ? Html::encode($tip) : null,
            'warning' => $warning !== null ? Html::encode($warning) : null,
            'orientation' => $this->orientation($element, $static),
            'translatable' => $translatable,
            'translationDescription' => $this->translationDescription($element, $static),
            'actionMenuItems' => $actionMenuItems,
            // show errors regardless of whether the field is static
            'errors' => $this->errors($element),
        ]);
    }

    /**
     * Returns the HTML for an element’s thumbnail.
     *
     * @param ElementInterface $element The element the field is associated with
     * @param int $size The maximum width and height the thumbnail should have.
     * @return string|null
     */
    public function thumbHtml(ElementInterface $element, int $size): ?string
    {
        return null;
    }

    /**
     * Returns the field’s preview HTMl.
     *
     * @param ElementInterface $element The element the form is being rendered for
     * @return string
     */
    public function previewHtml(ElementInterface $element): string
    {
        $attribute = $this->attribute();
        return ElementHelper::attributeHtml($element->$attribute);
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
     * @inheritdoc
     */
    protected function containerAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        return ArrayHelper::merge(parent::containerAttributes($element, $static), [
            'data' => [
                'base-input-name' => Craft::$app->getView()->namespaceInputName($this->baseInputName()),
                'error-key' => $this->errorKey(),
            ],
        ]);
    }

    /**
     * Returns the base input name for the field (sans namespace).
     *
     * @return string
     * @since 5.0.0
     */
    protected function baseInputName(): string
    {
        return $this->attribute();
    }

    /**
     * Returns the error key this field should be associated with.
     *
     * @return string
     * @since 5.0.0
     */
    protected function errorKey(): string
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
     * Returns whether the field should show a status indicator when modified.
     *
     * @return bool
     * @since 5.8.0
     */
    protected function showStatus(): bool
    {
        return true;
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
            return StringHelper::toString($status[0]);
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

    /**
     * Returns whether field supports copying its value across sites.
     *
     * @param ElementInterface $element
     * @return bool
     */
    public function isCrossSiteCopyable(ElementInterface $element): bool
    {
        return false;
    }

    /**
     * Returns any action menu items that should be shown for the field.
     *
     * See [[\craft\helpers\Cp::disclosureMenu()]] for documentation on supported item properties.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return array
     * @since 5.6.0
     */
    protected function actionMenuItems(?ElementInterface $element = null, bool $static = false): array
    {
        return [];
    }

    /**
     * Returns a “Copy field handle” action menu item definition for [[actionMenuItems()]].
     *
     * @param array $config
     * @return array
     * @since 5.9.0
     */
    protected function copyAttributeAction(array $config = []): array
    {
        $config += [
            'id' => sprintf('action-copy-handle-%s', mt_rand()),
            'icon' => 'clipboard',
            'label' => Craft::t('app', 'Copy attribute name'),
            'promptLabel' => Craft::t('app', 'Attribute Name'),
            'attribute' => $this->attribute(),
        ];

        $view = Craft::$app->getView();

        $view->registerJsWithVars(fn($id, $promptLabel, $attribute) => <<<JS
(() => {
  $('#' + $id).on('activate', () => {
    Craft.ui.createCopyTextPrompt({
      label: $promptLabel,
      value: $attribute,
    });
  });
})();
JS, [
            $view->namespaceInputId($config['id']),
            $config['promptLabel'],
            $config['attribute'],
        ]);

        return [
            'id' => $config['id'],
            'icon' => $config['icon'],
            'label' => $config['label'],
        ];
    }

    /**
     * Return the HTML that should be shown for the native field in the card preview.
     * It can be used outside an element context, e.g. in a card view designer.
     *
     * @param mixed $value
     * @param ElementInterface|null $element
     * @return string
     * @since 5.5.0
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (!$this->previewable()) {
            return '';
        }

        if ($value !== null) {
            return $value;
        }

        if ($element !== null) {
            return $element->{$this->attribute()};
        }

        return $this->label();
    }
}
