<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

class Tab extends FormContainer
{
    protected string|Closure|null $label = null;

    protected bool|Closure $hasErrors = false;

    private bool $hideNavigation = false;

    private bool $withinTabs = false;

    public static function make(
        ?string $key = null,
        string|Closure|null $label = null,
        iterable|Closure $children = [],
    ): static {
        $tab = parent::make()->children($children);

        if ($key !== null) {
            $tab->key($key);
        }

        if ($label !== null) {
            $tab->label($label);
        }

        return $tab;
    }

    public static function formElementType(): string
    {
        return 'craft:tab';
    }

    public function label(string|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function hasErrors(bool|Closure $hasErrors = true): static
    {
        $this->hasErrors = $hasErrors;

        return $this;
    }

    public function renderForTabs(bool $hideNavigation): string
    {
        $tab = clone $this;
        $tab->hideNavigation = $hideNavigation;
        $tab->withinTabs = true;

        return $tab->toHtml();
    }

    #[Override]
    public function toHtml(): string
    {
        if (! $this->withinTabs) {
            throw new InvalidArgumentException(sprintf(
                '%s must be rendered within %s.',
                static::class,
                Tabs::class,
            ));
        }

        return parent::toHtml();
    }

    #[Override]
    protected function tagName(): string
    {
        return 'craft-tab';
    }

    /** @return array<string, mixed> */
    #[Override]
    protected function formElementProps(): array
    {
        $hasErrors = $this->evaluate($this->hasErrors);

        if (! is_bool($hasErrors)) {
            $this->unsupportedOutputOption('hasErrors', 'Form');
        }

        return array_filter([
            'label' => $this->portableText('label', $this->label),
            'hasErrors' => $hasErrors ?: null,
        ], fn (mixed $value): bool => $value !== null);
    }

    #[Override]
    protected function hostAttributes(): array
    {
        return [
            ...parent::hostAttributes(),
            'slot' => 'tab',
            'style' => $this->hideNavigation ? 'display: none;' : null,
        ];
    }

    #[Override]
    protected function renderMarkup(): string
    {
        $key = $this->resolvedElementKey('HTML');
        $width = $this->resolvedColumnWidth('HTML');

        return parent::renderMarkup().Html::tag(
            'craft-field-group',
            $this->renderSlot(static::DEFAULT_SLOT, $this->children),
            [
                'slot' => 'panel',
                'data-form-tab-panel' => $key,
                'style' => $width !== null ? "width: {$width}%;" : null,
            ],
        );
    }

    #[Override]
    protected function renderSlots(): string
    {
        $label = $this->evaluate($this->label);
        $hasErrors = $this->evaluate($this->hasErrors);

        if (! is_string($label)) {
            $this->unsupportedOutputOption('label', 'HTML');
        }

        if (! is_bool($hasErrors)) {
            $this->unsupportedOutputOption('hasErrors', 'HTML');
        }

        return Html::encode($label).($hasErrors
            ? $this->renderContent(new HtmlString(Html::tag('craft-indicator', '', [
                'fill' => 'danger',
                'label' => t('Contains errors'),
            ])))
            : '');
    }
}
