<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementAttributeRenderer;
use CraftCms\Cms\Field\Icon;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutActionMenuItemsResolving;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Controls\Checkbox;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Controls\Textarea;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Action;
use CraftCms\Cms\Form\Nodes\ActionMenu;
use CraftCms\Cms\Form\Nodes\CopyAttribute;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\currentUser;
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
     * @var string Whether the instructions should be displayed before or after the input.
     */
    public string $instructionsPosition = 'before';

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
     *
     * @return list<array{label: string, value: string}>|null
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
     *
     * @return list<array{label: string, value: string}>|null
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

        // $indicatorHtml = implode('', array_map(fn (array $indicator) => Html::tag('craft-icon', Icons::svg($indicator['icon'], altText: $indicator['label']), [
        //     'class' => ['cp-icon', 'w-[0.75em]', 'text-fill-normal'],
        //     'data-color' => $indicator['iconColor'] ?? null,
        //     'title' => $indicator['label'],
        // ]), $this->selectorIndicators()));

        $indicatorHtml = implode('', array_map(fn (array $indicator) => Html::tag('craft-icon', '', [
            'name' => $indicator['icon'],
            'label' => $indicator['label'],
            'data-color' => $indicator['iconColor'] ?? null,
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
                'class' => ['text-xs', 'font-light', 'font-mono', 'fld-attribute-label'],
                'title' => $this->attribute(),
            ]).
            Html::endTag('div'); // .fld-attribute

        if ($indicatorHtml) {
            $innerHtml .= Html::tag('div', $indicatorHtml, [
                'class' => ['fld-field-indicators', 'flex', 'flex-nowrap', 'gap-1', 'mt-1'],
            ]);
        }

        $html = Html::tag('div', $innerHtml, [
            'class' => ['field-name'],
        ]);

        if ($icon) {
            return Html::tag('div', Icons::svg($icon), [
                'class' => ['fld-element-icon'],
            ]).$html;
        }

        return $html;
    }

    /**
     * Returns HTML attributes that should be added to the selector container.
     *
     * @return array{class: string, data: array{attribute: string, mandatory: bool, requirable: bool, thumbable: bool, preview-options: list<array{label: string, value: string}>|null, thumb-options: list<array{label: string, value: string}>|null}}
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
     *
     * @return list<array{label: string, icon: string, iconColor: string}>
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

    #[Override]
    public function hasCustomWidth(): bool
    {
        return true;
    }

    #[Override]
    public function hasSettings(): bool
    {
        return true;
    }

    #[Override]
    protected function settingsNodes(FormContext $context): array
    {
        return [
            $this->labelSettingsNode($context),
            ...$this->instructionsSettingsNodes($context),
            ...$this->noticeSettingsNodes($context),
        ];
    }

    /**
     * The Label field, with the “Hide” toggle in its actions slot. Hiding the
     * label disables the text input, mirroring the value the layout stores.
     */
    protected function labelSettingsNode(FormContext $context): Field
    {
        $labelHidden = ! $this->showLabel();

        return Field::make(t('Label'), Text::make('label')
            ->value($labelHidden ? null : $this->label)
            ->placeholder($this->defaultLabel())
            ->mode($labelHidden ? ControlMode::Disabled : ControlMode::Editable))
            ->actions(Action::make(
                Checkbox::make('labelHidden')->label(t('Hide'))->value($labelHidden),
            ));
    }

    /** @return list<Node> */
    protected function instructionsSettingsNodes(FormContext $context): array
    {
        return [
            Field::make(t('Instructions'), Textarea::make('instructions')
                ->value($this->instructions)
                ->placeholder($this->defaultInstructions())),
            Field::make(t('Instructions position'), Choice::make('instructionsPosition')
                ->options([
                    ['label' => t('Before the input'), 'value' => 'before'],
                    ['label' => t('After the input'), 'value' => 'after'],
                ])
                ->value($this->instructionsPosition)),
        ];
    }

    /** @return list<Node> */
    protected function noticeSettingsNodes(FormContext $context): array
    {
        return [
            Field::make(t('Tip'), Textarea::make('tip')->rows(1)->value($this->tip)),
            Field::make(t('Warning'), Textarea::make('warning')->rows(1)->value($this->warning)),
        ];
    }

    #[Override]
    public function formNode(FieldLayoutElementContext $context): ?Node
    {
        $control = $this->formControl($context);

        if ($control === null) {
            return null;
        }

        if ($context->mode !== ControlMode::Editable) {
            $control->mode($context->mode);
        }

        $static = $context->mode !== ControlMode::Editable;
        $status = $this->showStatus() ? $this->statusClass($context->element, $static) : null;

        return Field::make(
            $this->showLabel() ? $this->label() : null,
            $control,
        )
            ->instructions($this->instructionsText($context->element))
            ->instructionsPosition($this->instructionsPosition)
            ->tip($this->tipText($context->element))
            ->warning($this->warningText($context->element))
            ->required($this->required)
            ->status(
                $status,
                $status !== null
                    ? ($this->statusLabel($context->element, $static) ?? ucfirst($status))
                    : null,
            )
            ->layoutUid($this->uid)
            ->width($this->width)
            ->actions(...$this->formActionNodes($context, $control));
    }

    /**
     * The nodes rendered into the field heading's `actions` slot: the field's
     * “⋮” action menu, and — for admins with the “Show field handles in edit
     * forms” preference — the copyable handle chip.
     *
     * @return list<Node>
     */
    protected function formActionNodes(FieldLayoutElementContext $context, Control $control): array
    {
        $nodes = [];
        $uidPrefix = $this->formActionsUid($control);

        $items = $this->resolveActionMenuItems($context);
        if ($items !== []) {
            $nodes[] = ActionMenu::make("$uidPrefix:menu", $items)
                ->label(t('Field actions'));
        }

        if ($this->showAttribute() && $this->showFieldHandles()) {
            $nodes[] = CopyAttribute::make("$uidPrefix:handle", $this->attribute());
        }

        return $nodes;
    }

    /**
     * Collects the field's action menu items, giving plugins a chance to amend
     * them.
     *
     * @return list<array<string, mixed>>
     */
    protected function resolveActionMenuItems(FieldLayoutElementContext $context): array
    {
        $static = $context->mode !== ControlMode::Editable;

        event($event = new FieldLayoutActionMenuItemsResolving(
            $context->element,
            $this->actionMenuItems($context->element, $static),
            $static,
            $this,
        ));

        return $event->items;
    }

    /**
     * A stable, form-unique key for a field's action nodes.
     *
     * Derived from the control's path rather than the layout element's UID:
     * the UID is nullable (fluently-built layouts and the card view designer
     * produce UID-less elements), and one layout element can emit several
     * Fields (see {@see Addresses\LatLongField::formNode()}). Control paths are
     * already unique within a form namespace — {@see FormResolver}
     * rejects duplicates — and control-less nodes are scoped by that same
     * namespace.
     */
    protected function formActionsUid(Control $control): string
    {
        $path = $control->path();

        return 'field-actions:'.(is_array($path) ? implode('.', $path) : $path);
    }

    /** Whether the current user has opted into seeing field handles on edit forms. */
    protected function showFieldHandles(): bool
    {
        $user = currentUser();

        return (bool) ($user?->isAdmin() && $user->getPreference('showFieldHandles'));
    }

    protected function formControl(FieldLayoutElementContext $context): ?Control
    {
        return null;
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

        return app(ElementAttributeRenderer::class)->attributeHtml($element->$attribute);
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
            $this->instructionsText($element, $static) ? $this->instructionsId() : null,
            $this->tipText($element, $static) ? $this->tipId() : null,
            $this->warningText($element, $static) ? $this->warningId() : null,
        ]);

        return $ids ? implode(' ', $ids) : null;
    }

    /** @return array{class?: list<string>, data: array{base-input-name: string, error-key: string}} */
    #[Override]
    protected function containerAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        return Arr::merge(parent::containerAttributes($element, $static), [
            'data' => [
                'base-input-name' => InputNamespace::namespaceInputName($this->baseInputName()),
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
     * @return array<string, scalar|array<array-key, scalar|null>|null>
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
     * @return array<string, scalar|array<array-key, scalar|null>|null>
     */
    protected function labelAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        return [];
    }

    /**
     * Returns or sets the field’s label.
     */
    public function label(?string $label = null): static|string|null
    {
        if (func_num_args() !== 0) {
            $this->label = $label;

            return $this;
        }

        if (isset($this->label) && $this->label !== '' && $this->label !== '__blank__') {
            return t($this->label, category: 'site');
        }

        return $this->defaultLabel();
    }

    public function instructions(?string $instructions): static
    {
        $this->instructions = $instructions;

        return $this;
    }

    public function instructionsPosition(string $position): static
    {
        if (! in_array($position, ['before', 'after'], true)) {
            throw new InvalidArgumentException("Invalid instructions position: $position");
        }

        $this->instructionsPosition = $position;

        return $this;
    }

    public function tip(?string $tip): static
    {
        $this->tip = $tip;

        return $this;
    }

    public function warning(?string $warning): static
    {
        $this->warning = $warning;

        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function labelHidden(bool $labelHidden = true): static
    {
        return $this->label($labelHidden ? '__blank__' : null);
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
    protected function instructionsText(?ElementInterface $element = null, bool $static = false): ?string
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
    /**
     * Returns the field’s tip text.
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     */
    protected function tipText(?ElementInterface $element = null, bool $static = false): ?string
    {
        return $this->tip ? t($this->tip, category: 'site') : null;
    }

    /**
     * Returns the field’s warning text.
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     */
    protected function warningText(?ElementInterface $element = null, bool $static = false): ?string
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
     * See [[\CraftCms\Cms\Cp\Html\MenuHtml::disclosureMenu()]] for documentation on supported item properties.
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     * @return list<array<string, mixed>>
     */
    protected function actionMenuItems(?ElementInterface $element = null, bool $static = false): array
    {
        return [];
    }

    /**
     * Returns a “Copy field handle” action menu item definition for [[actionMenuItems()]].
     *
     * @param  array{id?: string, icon?: string, label?: string, promptLabel?: string, attribute?: string}  $config
     * @return array{id: string, icon: string, label: string}
     */
    protected function copyAttributeAction(array $config = []): array
    {
        $config += [
            'id' => sprintf('action-copy-handle-%s', mt_rand()),
            'icon' => 'clipboard',
            'label' => t('Copy attribute name'),
            'promptLabel' => t('Attribute Name'),
            'attribute' => $this->attribute(),
        ];

        return [
            // The `action-copy-` prefix is load-bearing: `ElementHtml` marks it
            // `data-copy-action` when the menu is shown on a chip.
            'id' => $config['id'],
            'icon' => $config['icon'],
            'label' => $config['label'],
            // Declarative rather than registered JS, which would never reach an
            // Inertia-rendered page. `craft:copy-text-prompt` is handled by the
            // slideout module's window listener, which shows the same
            // copy-to-clipboard dialog the legacy handler did.
            'action' => [
                'type' => 'event',
                'name' => 'craft:copy-text-prompt',
                'detail' => [
                    'label' => $config['promptLabel'],
                    'value' => $config['attribute'],
                ],
            ],
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
