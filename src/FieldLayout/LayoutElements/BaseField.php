<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use CraftCms\Cms\FieldLayout\Events\DefineActionMenuItems;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use Override;

use function CraftCms\Cms\t;

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
     * {@inheritdoc}
     */
    public function __construct($config = [])
    {
        if (Arr::pull($config, 'labelHidden')) {
            $config['label'] = '__blank__';
        }

        parent::__construct($config);
    }

    /**
     * Returns the element attribute this field is for.
     */
    abstract public function attribute(): string;

    /**
     * Returns the key for this field.
     */
    public function key(): string
    {
        $uid = $this->uid ?? '{uid}';

        return "layoutElement:$uid";
    }

    /**
     * Returns whether the attribute should be shown for admin users with “Show field handles in edit forms” enabled.
     */
    public function showAttribute(): bool
    {
        return false;
    }

    /**
     * Returns the field’s value.
     */
    protected function value(?ElementInterface $element = null): mixed
    {
        return $element->{$this->attribute()} ?? null;
    }

    /**
     * Returns the field’s validation errors.
     *
     * @return string[]
     */
    protected function fieldErrors(?ElementInterface $element = null): array
    {
        if (! $element) {
            return [];
        }

        return $element->errors()->get($this->attribute());
    }

    /**
     * Returns whether the field *must* be present within the layout.
     */
    public function mandatory(): bool
    {
        return false;
    }

    /**
     * Returns whether the field can optionally be marked as required.
     */
    public function requirable(): bool
    {
        return false;
    }

    /**
     * Returns whether the field can be chosen as elements’ thumbnail provider.
     */
    public function thumbable(): bool
    {
        return false;
    }

    /**
     * Returns whether the field can be included in element cards.
     */
    public function previewable(): bool
    {
        return false;
    }

    /**
     * Returns the card preview options supplied by this field.
     */
    public function getPreviewOptions(): ?array
    {
        if (! $this->previewable()) {
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
     * Returns the card thumbnail options supplied by this field.
     */
    public function getThumbOptions(): ?array
    {
        if (! $this->thumbable()) {
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
     * {@inheritdoc}
     */
    public function selectorHtml(): string
    {
        return Html::tag('div', $this->selectorInnerHtml(), $this->selectorAttributes());
    }

    /**
     * Returns the selector’s inner HTML.
     */
    protected function selectorInnerHtml(): string
    {
        $innerHtml = '';

        $label = $this->selectorLabel();
        $icon = $this->selectorIcon();

        $indicatorHtml = implode('', array_map(fn (array $indicator) => Html::tag('div', Cp::iconSvg($indicator['icon'], altText: $indicator['label']), [
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
            ]).
            Html::tag('div', $this->attribute(), [
                'class' => ['smalltext', 'light', 'code', 'fld-attribute-label'],
                'title' => $this->attribute(),
            ]).
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
            return Html::tag('div', Cp::iconSvg($icon), [
                'class' => ['cp-icon', 'medium'],
            ]).$html;
        }

        return $html;
    }

    /**
     * Returns HTML attributes that should be added to the selector container.
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
                'thumb-options' => $this->getThumbOptions(),
            ],
        ];
    }

    /**
     * Returns the selector label.
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
     */
    protected function selectorIcon(): ?string
    {
        return null;
    }

    /**
     * Returns the indicators that should be shown within the selector.
     */
    protected function selectorIndicators(): array
    {
        $indicators = [];

        if ($this->requirable() && $this->required) {
            $indicators[] = [
                'label' => t('This field is required'),
                'icon' => 'asterisk',
                'iconColor' => 'rose',
            ];
        }

        if (isset($this->tip)) {
            $indicators[] = [
                'label' => t('This field has a tip'),
                'icon' => 'lightbulb',
                'iconColor' => 'sky',
            ];
        }

        if (isset($this->warning)) {
            $indicators[] = [
                'label' => t('This field has a warning'),
                'icon' => 'alert',
                'iconColor' => 'amber',
            ];
        }

        if ($this->hasConditions()) {
            $indicators[] = [
                'label' => t('This field is conditional'),
                'icon' => 'diamond',
                'iconColor' => 'orange',
            ];
        }

        return $indicators;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function hasCustomWidth(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function hasSettings(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('_includes/forms/fld/field-settings.twig', [
            'field' => $this,
            'defaultLabel' => $this->defaultLabel(),
            'defaultInstructions' => $this->defaultInstructions(),
            'labelHidden' => ! $this->showLabel(),
        ]);
    }

    /**
     * {@inheritdoc}
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

        event($event = new DefineActionMenuItems($element, $actionMenuItems, $static));
        $actionMenuItems = $event->items;

        if (
            $this->uid &&
            $element?->id &&
            ! $static &&
            $this->isCrossSiteCopyable($element) &&
            $this->translatable($element, $static) &&
            $element->getIsCrossSiteCopyable()
        ) {
            // prepare namespace for the purpose of copying
            $namespace = Craft::$app->getView()->getNamespace();

            $actionMenuItems = array_filter([
                [
                    'icon' => 'clone',
                    'label' => t('Copy value from site…'),
                    'attributes' => [
                        'data' => [
                            'cross-site-copy' => true,
                            'element-id' => $element->id,
                            'layout-element' => $this->uid,
                            'label' => $label,
                            'namespace' => ($namespace && $namespace !== 'fields')
                                ? Str::chopEnd($namespace, '[fields]')
                                : null,
                        ],
                    ],
                ],
                ! empty($actionMenuItems) ? ['type' => 'hr'] : null,
                ...$actionMenuItems,
            ]);
        }

        return Cp::fieldHtml($inputHtml, [
            'fieldClass' => array_keys(array_filter([
                'no-status' => ! $showStatus,
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
            'required' => ! $static && $this->required,
            'instructions' => $instructions !== null ? Html::encode($instructions) : null,
            'tip' => $tip !== null ? Html::encode($tip) : null,
            'warning' => $warning !== null ? Html::encode($warning) : null,
            'orientation' => $this->orientation($element, $static),
            'translatable' => $translatable,
            'translationDescription' => $this->translationDescription($element, $static),
            'actionMenuItems' => $actionMenuItems,
            // show errors regardless of whether the field is static
            'errors' => $this->fieldErrors($element),
        ]);
    }

    /**
     * Returns the HTML for an element’s thumbnail.
     *
     * @param  ElementInterface  $element  The element the field is associated with
     * @param  int  $size  The maximum width and height the thumbnail should have.
     */
    public function thumbHtml(ElementInterface $element, int $size): ?string
    {
        return null;
    }

    /**
     * Returns the field’s preview HTMl.
     *
     * @param  ElementInterface  $element  The element the form is being rendered for
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
     */
    protected function useFieldset(): bool
    {
        return false;
    }

    /**
     * Returns the `id` of the input.
     */
    protected function id(): string
    {
        return $this->attribute();
    }

    /**
     * Returns the `id` of the field label.
     */
    protected function labelId(): string
    {
        return sprintf('%s-label', $this->id());
    }

    /**
     * Returns the `id` of the field instructions.
     */
    protected function instructionsId(): string
    {
        return sprintf('%s-instructions', $this->id());
    }

    /**
     * Returns the `id` of the field tip.
     */
    protected function tipId(): string
    {
        return sprintf('%s-tip', $this->id());
    }

    /**
     * Returns the `id` of the field warning.
     */
    protected function warningId(): string
    {
        return sprintf('%s-warning', $this->id());
    }

    /**
     * Returns the `id` of the field errors.
     */
    protected function errorsId(): string
    {
        return sprintf('%s-errors', $this->id());
    }

    /**
     * Returns the `id` if the field status message.
     */
    protected function statusId(): string
    {
        return sprintf('%s-status', $this->id());
    }

    /**
     * Returns the `aria-describedby` attribute value that should be set on the focusable input(s).
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     *
     * @see inputHtml()
     */
    protected function describedBy(?ElementInterface $element = null, bool $static = false): ?string
    {
        $ids = array_filter([
            (! $static && $this->fieldErrors($element)) ? $this->errorsId() : null,
            $this->statusClass($element, $static) ? $this->statusId() : null,
            $this->instructions($element, $static) ? $this->instructionsId() : null,
            $this->tip($element, $static) ? $this->tipId() : null,
            $this->warning($element, $static) ? $this->warningId() : null,
        ]);

        return $ids ? implode(' ', $ids) : null;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function containerAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        return Arr::merge(parent::containerAttributes($element, $static), [
            'data' => [
                'base-input-name' => Craft::$app->getView()->namespaceInputName($this->baseInputName()),
                'error-key' => $this->errorKey(),
            ],
        ]);
    }

    /**
     * Returns the base input name for the field (sans namespace).
     */
    protected function baseInputName(): string
    {
        return $this->attribute();
    }

    /**
     * Returns the error key this field should be associated with.
     */
    protected function errorKey(): string
    {
        return $this->attribute();
    }

    /**
     * Returns input container HTML attributes.
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     */
    protected function inputContainerAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        return [];
    }

    /**
     * Returns label HTML attributes.
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     */
    protected function labelAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        return [];
    }

    /**
     * Returns the field’s label.
     */
    public function label(): ?string
    {
        if (isset($this->label) && $this->label !== '' && $this->label !== '__blank__') {
            return t($this->label, category: 'site');
        }

        return $this->defaultLabel();
    }

    /**
     * Returns the field’s default label, which will be used if [[label]] is null.
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     */
    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return null;
    }

    /**
     * Returns whether the label should be shown in form inputs.
     */
    protected function showLabel(): bool
    {
        return $this->label !== '__blank__';
    }

    /**
     * Returns whether the field should show a status indicator when modified.
     */
    protected function showStatus(): bool
    {
        return true;
    }

    /**
     * Returns the field’s status class.
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     */
    protected function statusClass(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($element && ($status = $element->getAttributeStatus($this->attribute()))) {
            return Str::toString($status[0]);
        }

        return null;
    }

    /**
     * Returns the field’s status label.
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
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
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     */
    protected function instructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return $this->instructions ? t($this->instructions, category: 'site') : $this->defaultInstructions($element, $static);
    }

    /**
     * Returns the field’s default instructions, which will be used if [[instructions]] is null.
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     */
    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return null;
    }

    /**
     * Returns the field’s input HTML.
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     */
    abstract protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string;

    /**
     * Returns the field’s tip text.
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     */
    protected function tip(?ElementInterface $element = null, bool $static = false): ?string
    {
        return $this->tip ? t($this->tip, category: 'site') : null;
    }

    /**
     * Returns the field’s warning text.
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     */
    protected function warning(?ElementInterface $element = null, bool $static = false): ?string
    {
        return $this->warning ? t($this->warning, category: 'site') : null;
    }

    /**
     * Returns the field’s orientation (`ltr` or `rtl`).
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     */
    protected function orientation(?ElementInterface $element = null, bool $static = false): string
    {
        // If there’s only one site, go with its language
        if (! Sites::isMultiSite()) {
            // Only one site so use its language
            $locale = Sites::getPrimarySite()->getLocale();
        } elseif (! $element || ! $this->translatable($element, $static)) {
            // Not translatable, so use the user’s language
            $locale = I18N::getLocale();
        } else {
            // Use the site’s language
            $locale = $element->getSite()->getLocale();
        }

        return $locale->getOrientation();
    }

    /**
     * Returns whether the field is translatable.
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     */
    protected function translatable(?ElementInterface $element = null, bool $static = false): bool
    {
        return false;
    }

    /**
     * Returns the descriptive text for how this field is translatable.
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     */
    protected function translationDescription(?ElementInterface $element = null, bool $static = false): ?string
    {
        return null;
    }

    /**
     * Returns whether field supports copying its value across sites.
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
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     */
    protected function actionMenuItems(?ElementInterface $element = null, bool $static = false): array
    {
        return [];
    }

    /**
     * Returns a “Copy field handle” action menu item definition for [[actionMenuItems()]].
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

        $view->registerJsWithVars(fn ($id, $promptLabel, $attribute) => <<<JS
(() => {
  $('#' + $id).on('activate', () => {
    Craft.ui.createCopyTextPrompt({
      label: $promptLabel,
      value: $attribute,
    })
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
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $this->previewable()) {
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
