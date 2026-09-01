<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Support\Facades\Markdown;
use CraftCms\Cms\Support\Html;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Override;
use Stringable;
use Twig\Markup;

use function CraftCms\Cms\t;

/**
 * PHP counterpart to the `<craft-field>` web component — the generic form
 * field shell (label, instructions, tip/warning, errors, status) around any
 * control.
 *
 *     Field::make()
 *         ->label(t('Handle'))
 *         ->required()
 *         ->instructions(t('How you’ll refer to this field in the templates.'))
 *         ->input(FormFields::textHtml(['name' => 'handle']))
 *         ->errors($model->errors()->get('handle'));
 */
class Field extends ViewComponent
{
    use HasDisabled;
    use HasId;

    protected string|Htmlable|Stringable|ViewComponent|null $label = null;

    protected string|Htmlable|Stringable|ViewComponent|null $input = null;

    protected string|Stringable|null $instructions = null;

    protected string $instructionsPosition = 'before';

    protected bool $required = false;

    protected bool $translatable = false;

    protected ?string $translationDescription = null;

    protected bool $fieldset = false;

    protected ?string $status = null;

    protected ?string $statusLabel = null;

    protected ?string $orientation = null;

    protected bool $readOnly = false;

    protected ?string $width = null;

    protected string|Htmlable|Stringable|ViewComponent|null $headingPrefix = null;

    protected string|Htmlable|Stringable|ViewComponent|null $headingSuffix = null;

    /** @var array<array-key, string> */
    protected array $errors = [];

    protected string|Stringable|null $tip = null;

    protected string|Stringable|null $warning = null;

    protected string|Htmlable|Stringable|ViewComponent|null $labelExtra = null;

    protected string|Htmlable|Stringable|ViewComponent|null $actions = null;

    protected function tagName(): string
    {
        return 'craft-field';
    }

    /** A string renders as the `label` attribute; markup or a component renders into the label slot. */
    public function label(string|Htmlable|Stringable|ViewComponent|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * The wrapped control. Strings are treated as trusted HTML (matching
     * `FormFields`), and should have a single root element so the `slot`
     * attribute lands on the control itself rather than a wrapper.
     */
    public function input(string|Htmlable|Stringable|ViewComponent|null $input): static
    {
        $this->input = $input;

        return $this;
    }

    /** Field instructions; supports the same markdown as the Twig field macro. */
    public function instructions(string|Stringable|null $instructions): static
    {
        $this->instructions = $instructions;

        return $this;
    }

    /** @param 'before'|'after' $position Relative to the input. */
    public function instructionsPosition(string $position): static
    {
        $this->instructionsPosition = $position;

        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function translatable(bool $translatable = true, ?string $description = null): static
    {
        $this->translatable = $translatable;

        if ($description !== null) {
            $this->translationDescription = $description;
        }

        return $this;
    }

    /** Renders the label as a group legend (`role="group"`) instead of a `<label>`. */
    public function fieldset(bool $fieldset = true): static
    {
        $this->fieldset = $fieldset;

        return $this;
    }

    public function status(?string $status, ?string $label = null): static
    {
        $this->status = $status;

        if ($label !== null) {
            $this->statusLabel = $label;
        }

        return $this;
    }

    /** @param 'ltr'|'rtl'|null $orientation */
    public function orientation(?string $orientation): static
    {
        $this->orientation = $orientation;

        return $this;
    }

    /** Marks the field read-only (renders the "Read Only" badge). */
    public function readOnly(bool $readOnly = true): static
    {
        $this->readOnly = $readOnly;

        return $this;
    }

    /** Heading content rendered before the label. Strings are trusted HTML. */
    public function headingPrefix(string|Htmlable|Stringable|ViewComponent|null $headingPrefix): static
    {
        $this->headingPrefix = $headingPrefix;

        return $this;
    }

    /** Heading content rendered after the label extras. Strings are trusted HTML. */
    public function headingSuffix(string|Htmlable|Stringable|ViewComponent|null $headingSuffix): static
    {
        $this->headingSuffix = $headingSuffix;

        return $this;
    }

    /** @param array<array-key, string> $errors Plain-text messages; encoded on render. */
    public function errors(array $errors): static
    {
        $this->errors = $errors;

        return $this;
    }

    /** Tip notice; supports inline markdown. */
    public function tip(string|Stringable|null $tip): static
    {
        $this->tip = $tip;

        return $this;
    }

    /** Warning notice; supports inline markdown. */
    public function warning(string|Stringable|null $warning): static
    {
        $this->warning = $warning;

        return $this;
    }

    /**
     * Extra heading content. Strings are trusted HTML.
     */
    #[\Deprecated(message: 'in 6.0. [[actions()]] should be used instead.')]
    public function labelExtra(string|Htmlable|Stringable|ViewComponent|null $labelExtra): static
    {
        $this->labelExtra = $labelExtra;

        return $this;
    }

    /**
     * Field-level actions (hide-label toggles, copy-value buttons, field
     * settings menus). Strings are trusted HTML.
     */
    public function actions(string|Htmlable|Stringable|ViewComponent|null $actions): static
    {
        $this->actions = $actions;

        return $this;
    }

    /**
     * Overrides the inferred width behavior. By default the field spans its
     * column, unless the slotted control declares a `maxlength` — which
     * shrinks it to the control's width. `full` spans the column despite a
     * `maxlength`; `auto` shrinks without one.
     *
     * @param  'full'|'auto'|null  $width
     */
    public function width(?string $width): static
    {
        $this->width = $width;

        return $this;
    }

    #[Override]
    protected function hostAttributes(): array
    {
        return [
            'id' => $this->getId(),
            'label' => is_string($this->label)
            || ($this->label instanceof Stringable && ! $this->label instanceof Htmlable && ! $this->label instanceof Markup)
                ? $this->label
                : null,
            'required' => $this->required,
            'translatable' => $this->translatable,
            'translation-description' => $this->translationDescription,
            'fieldset' => $this->fieldset,
            'status' => $this->status,
            'status-label' => $this->statusLabel,
            'orientation' => $this->orientation,
            'width' => $this->width,
            'readonly' => $this->readOnly,
            'disabled' => $this->isDisabled(),
            'has-errors' => $this->normalizedErrors() !== [],
            'instructions-position' => $this->instructionsPosition === 'after' ? 'after' : null,
        ];
    }

    #[Override]
    protected function renderSlots(): string
    {
        $instructions = (string) ($this->instructions ?? '');
        $tip = (string) ($this->tip ?? '');
        $warning = (string) ($this->warning ?? '');
        $errors = $this->normalizedErrors();

        return implode('', array_filter([
            $this->label instanceof Htmlable || $this->label instanceof Markup
                ? $this->renderSlot('label', $this->label)
                : '',
            $this->renderSlot('input', $this->trustedHtml($this->input)),
            $instructions !== ''
                ? $this->renderSlot('help-text', new HtmlString(
                    Html::tag('div', app(ContentHtml::class)->parseMarkdown($instructions)),
                ))
                : '',
            $tip !== '' ? $this->renderSlot('tip', new HtmlString($this->parseNotice($tip))) : '',
            $warning !== '' ? $this->renderSlot('warning', new HtmlString($this->parseNotice($warning))) : '',
            $this->renderSlot('label-extra', $this->trustedHtml($this->labelExtra)),
            $this->renderSlot('actions', $this->trustedHtml($this->actions)),
            $this->renderSlot('heading-prefix', $this->trustedHtml($this->headingPrefix)),
            $this->renderSlot('heading-suffix', $this->trustedHtml($this->headingSuffix)),
            $errors !== [] ? $this->renderSlot('feedback', new HtmlString($this->errorListHtml($errors))) : '',
            parent::renderSlots(),
        ]));
    }

    /** @return array<array-key, string> */
    protected function normalizedErrors(): array
    {
        return array_values(array_filter($this->errors));
    }

    /** @param array<array-key, string> $errors */
    protected function errorListHtml(array $errors): string
    {
        return Html::tag(
            'ul',
            implode('', array_map(
                fn (string $error): string => Html::tag('li', Html::encode($error)),
                $errors,
            )),
            [
                'class' => 'error-list',
                'aria' => ['label' => t('Validation errors')],
            ],
        );
    }

    /**
     * Inline-markdown parsing for notices, matching the field wrapper's
     * tips/warnings. Wrapped in a `<span>` so mixed inline content slots as a
     * single root element.
     */
    protected function parseNotice(string $message): string
    {
        return Html::tag(
            'span',
            Html::decodeDoubles(Markdown::parseParagraph(Html::encodeInvalidTags($message))),
        );
    }

    /** Strings on trusted-HTML properties render unencoded, matching FormFields. */
    protected function trustedHtml(mixed $value): mixed
    {
        if (is_string($value) || ($value instanceof Stringable && ! $value instanceof Htmlable && ! $value instanceof ViewComponent)) {
            return new HtmlString((string) $value);
        }

        return $value;
    }
}
