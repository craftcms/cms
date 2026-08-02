<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\Forms\Data\FormElementData;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Translation\Locale;
use Override;

class MoneyInput extends ScalarInput
{
    protected string|Closure $type = 'text';

    protected string|Closure|null $inputmode = 'decimal';

    protected string|Closure|null $currency = null;

    protected string|Closure|null $currencyLabel = null;

    protected int|Closure $fractionDigits = 2;

    protected string|Closure|null $formattingLocale = null;

    protected string|Closure|null $decimalSeparator = null;

    protected string|Closure|null $groupSeparator = null;

    public static function formElementType(): string
    {
        return 'craft:money-input';
    }

    #[Override]
    protected function tagName(): string
    {
        return 'craft-input-money';
    }

    public function currency(string|Closure|null $currency): static
    {
        $this->trackConfiguration('currency');
        $this->currency = $currency;

        return $this;
    }

    public function currencyLabel(string|Closure|null $currencyLabel): static
    {
        $this->trackConfiguration('currencyLabel');
        $this->currencyLabel = $currencyLabel;

        return $this;
    }

    public function fractionDigits(int|Closure $fractionDigits): static
    {
        $this->trackConfiguration('fractionDigits');
        $this->fractionDigits = $fractionDigits;

        return $this;
    }

    public function formattingLocale(string|Closure|null $formattingLocale): static
    {
        $this->trackConfiguration('formattingLocale');
        $this->formattingLocale = $formattingLocale;

        return $this;
    }

    public function decimalSeparator(string|Closure|null $decimalSeparator): static
    {
        $this->trackConfiguration('decimalSeparator');
        $this->decimalSeparator = $decimalSeparator;

        return $this;
    }

    public function groupSeparator(string|Closure|null $groupSeparator): static
    {
        $this->trackConfiguration('groupSeparator');
        $this->groupSeparator = $groupSeparator;

        return $this;
    }

    #[Override]
    public function toFormElementData(): FormElementData
    {
        $this->rejectConfiguredOptions([
            'currencyLabel',
            'decimalSeparator',
            'formattingLocale',
            'groupSeparator',
        ], 'Form');

        return parent::toFormElementData();
    }

    #[Override]
    protected function formElementProps(): array
    {
        $fractionDigits = $this->evaluate($this->fractionDigits);

        if (! is_int($fractionDigits) || $fractionDigits < 0) {
            $this->unsupportedOutputOption('fractionDigits', 'Form');
        }

        return [
            'currency' => $this->portableText('currency', $this->currency),
            'fractionDigits' => $fractionDigits,
            'minorUnits' => true,
            'placeholder' => $this->portableText('placeholder', $this->placeholder),
        ];
    }

    #[Override]
    protected function unsupportedPortableOptions(): array
    {
        return ['min', 'max', 'step'];
    }

    #[Override]
    protected function hostAttributes(): array
    {
        $fractionDigits = $this->evaluate($this->fractionDigits);
        $locale = I18N::getFormattingLocale();

        if (! is_int($fractionDigits) || $fractionDigits < 0) {
            $this->unsupportedOutputOption('fractionDigits', 'HTML');
        }

        return [
            ...parent::hostAttributes(),
            'name' => $this->valueInputName(),
            'currency' => $this->evaluate($this->currency),
            'currency-label' => $this->evaluate($this->currencyLabel),
            'decimal-separator' => $this->evaluate($this->decimalSeparator)
                ?? $locale->getNumberSymbol(Locale::SYMBOL_DECIMAL_SEPARATOR),
            'fraction-digits' => $fractionDigits,
            'group-separator' => $this->evaluate($this->groupSeparator)
                ?? $locale->getNumberSymbol(Locale::SYMBOL_GROUPING_SEPARATOR),
        ];
    }

    #[Override]
    protected function renderSlots(): string
    {
        $name = $this->evaluate($this->name);
        $locale = $this->evaluate($this->formattingLocale) ?? I18N::getFormattingLocale()->id;
        $hidden = $name === null ? '' : (string) Html::hiddenInput("{$name}[locale]", $locale);

        return $hidden.parent::renderSlots();
    }

    #[Override]
    protected function inputHtml(): string
    {
        return Html::modifyTagAttributes(parent::inputHtml(), [
            'name' => $this->valueInputName(),
        ]);
    }

    private function valueInputName(): ?string
    {
        $name = $this->evaluate($this->name);

        return $name === null ? null : "{$name}[value]";
    }
}
