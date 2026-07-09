<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\Concerns\EvaluatesClosures;
use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Support\Facades\Markdown;
use CraftCms\Cms\Support\Html;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
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
 *         ->input(fn () => FormFields::textHtml(['name' => 'handle']))
 *         ->errors($model->errors()->get('handle'));
 *
 * Every setter accepts a literal value or a Closure evaluated at render time
 * (with dependency injection — see {@see EvaluatesClosures}).
 */
class Field extends ViewComponent
{
    use HasDisabled;
    use HasId;

    protected string|Htmlable|Stringable|ViewComponent|Closure|null $label = null;

    protected string|Htmlable|Stringable|ViewComponent|Closure|null $input = null;

    protected string|Stringable|Closure|null $instructions = null;

    protected string|Closure $instructionsPosition = 'before';

    protected bool|Closure $required = false;

    protected bool|Closure $translatable = false;

    protected string|Closure|null $translationDescription = null;

    protected bool|Closure $fieldset = false;

    protected string|Closure|null $status = null;

    protected string|Closure|null $statusLabel = null;

    protected string|Closure|null $orientation = null;

    protected bool|Closure $readOnly = false;

    protected string|Htmlable|ViewComponent|Closure|null $headingPrefix = null;

    protected string|Htmlable|ViewComponent|Closure|null $headingSuffix = null;

    /** @var array<array-key, string>|Closure */
    protected array|Closure $errors = [];

    protected string|Stringable|Closure|null $tip = null;

    protected string|Stringable|Closure|null $warning = null;

    protected string|Htmlable|ViewComponent|Closure|null $labelExtra = null;

    protected function tagName(): string
    {
        return 'craft-field';
    }

    /** A string renders as the `label` attribute; markup or a component renders into the label slot. */
    public function label(string|Htmlable|Stringable|ViewComponent|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * The wrapped control. Strings are treated as trusted HTML (matching
     * `FormFields`), and should have a single root element so the `slot`
     * attribute lands on the control itself rather than a wrapper.
     */
    public function input(string|Htmlable|Stringable|ViewComponent|Closure|null $input): static
    {
        $this->input = $input;

        return $this;
    }

    /** Field instructions; supports the same markdown as the Twig field macro. */
    public function instructions(string|Stringable|Closure|null $instructions): static
    {
        $this->instructions = $instructions;

        return $this;
    }

    /** @param 'before'|'after'|Closure $position Relative to the input. */
    public function instructionsPosition(string|Closure $position): static
    {
        $this->instructionsPosition = $position;

        return $this;
    }

    public function required(bool|Closure $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function translatable(bool|Closure $translatable = true, string|Closure|null $description = null): static
    {
        $this->translatable = $translatable;

        if ($description !== null) {
            $this->translationDescription = $description;
        }

        return $this;
    }

    /** Renders the label as a group legend (`role="group"`) instead of a `<label>`. */
    public function fieldset(bool|Closure $fieldset = true): static
    {
        $this->fieldset = $fieldset;

        return $this;
    }

    public function status(string|Closure|null $status, string|Closure|null $label = null): static
    {
        $this->status = $status;

        if ($label !== null) {
            $this->statusLabel = $label;
        }

        return $this;
    }

    /** @param 'ltr'|'rtl'|Closure|null $orientation */
    public function orientation(string|Closure|null $orientation): static
    {
        $this->orientation = $orientation;

        return $this;
    }

    /** Marks the field read-only (renders the "Read Only" badge). */
    public function readOnly(bool|Closure $readOnly = true): static
    {
        $this->readOnly = $readOnly;

        return $this;
    }

    /** Heading content rendered before the label. Strings are trusted HTML. */
    public function headingPrefix(string|Htmlable|ViewComponent|Closure|null $headingPrefix): static
    {
        $this->headingPrefix = $headingPrefix;

        return $this;
    }

    /** Heading content rendered after the label extras. Strings are trusted HTML. */
    public function headingSuffix(string|Htmlable|ViewComponent|Closure|null $headingSuffix): static
    {
        $this->headingSuffix = $headingSuffix;

        return $this;
    }

    /** @param array<array-key, string>|Closure $errors Plain-text messages; encoded on render. */
    public function errors(array|Closure $errors): static
    {
        $this->errors = $errors;

        return $this;
    }

    /** Tip notice; supports inline markdown. */
    public function tip(string|Stringable|Closure|null $tip): static
    {
        $this->tip = $tip;

        return $this;
    }

    /** Warning notice; supports inline markdown. */
    public function warning(string|Stringable|Closure|null $warning): static
    {
        $this->warning = $warning;

        return $this;
    }

    /** Extra heading content (handle-copy buttons, action menus). Strings are trusted HTML. */
    public function labelExtra(string|Htmlable|ViewComponent|Closure|null $labelExtra): static
    {
        $this->labelExtra = $labelExtra;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        $label = $this->evaluate($this->label);

        return [
            'id' => $this->getId(),
            'label' => is_string($label)
            || ($label instanceof Stringable && ! $label instanceof Htmlable && ! $label instanceof Markup)
                ? $label
                : null,
            'required' => (bool) $this->evaluate($this->required),
            'translatable' => (bool) $this->evaluate($this->translatable),
            'translation-description' => $this->evaluate($this->translationDescription),
            'fieldset' => (bool) $this->evaluate($this->fieldset),
            'status' => $this->evaluate($this->status),
            'status-label' => $this->evaluate($this->statusLabel),
            'orientation' => $this->evaluate($this->orientation),
            'readonly' => (bool) $this->evaluate($this->readOnly),
            'disabled' => $this->isDisabled(),
            'has-errors' => $this->evaluatedErrors() !== [],
            'instructions-position' => $this->evaluate($this->instructionsPosition) === 'after' ? 'after' : null,
        ];
    }

    #[\Override]
    protected function renderSlots(): string
    {
        $label = $this->evaluate($this->label);
        $instructions = (string) ($this->evaluate($this->instructions) ?? '');
        $tip = (string) ($this->evaluate($this->tip) ?? '');
        $warning = (string) ($this->evaluate($this->warning) ?? '');
        $errors = $this->evaluatedErrors();

        return implode('', array_filter([
            $label instanceof Htmlable || $label instanceof Markup || $label instanceof ViewComponent
                ? $this->renderSlot('label', $label)
                : '',
            $this->renderSlot('input', $this->trustedHtml($this->evaluate($this->input))),
            $instructions !== ''
                ? $this->renderSlot('help-text', new HtmlString(
                    Html::tag('div', app(ContentHtml::class)->parseMarkdown($instructions)),
                ))
                : '',
            $tip !== '' ? $this->renderSlot('tip', new HtmlString($this->parseNotice($tip))) : '',
            $warning !== '' ? $this->renderSlot('warning', new HtmlString($this->parseNotice($warning))) : '',
            $this->renderSlot('label-extra', $this->trustedHtml($this->evaluate($this->labelExtra))),
            $this->renderSlot('heading-prefix', $this->trustedHtml($this->evaluate($this->headingPrefix))),
            $this->renderSlot('heading-suffix', $this->trustedHtml($this->evaluate($this->headingSuffix))),
            $errors !== [] ? $this->renderSlot('feedback', new HtmlString($this->errorListHtml($errors))) : '',
            parent::renderSlots(),
        ]));
    }

    /** @return array<array-key, string> */
    protected function evaluatedErrors(): array
    {
        return array_values(array_filter((array) $this->evaluate($this->errors)));
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
