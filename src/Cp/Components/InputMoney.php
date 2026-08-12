<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Support\Html;

class InputMoney extends Input
{
    protected ?string $moneyName = null;

    protected string $currency = 'USD';

    protected string $locale = 'en-US';

    protected ?int $decimals = null;

    protected ?string $decimalSeparator = null;

    protected ?string $groupSeparator = null;

    protected bool $showCurrency = true;

    protected bool $clearable = true;

    #[\Override]
    protected function tagName(): string
    {
        return 'craft-input-money';
    }

    #[\Override]
    public function name(?string $name): static
    {
        $this->moneyName = $name;

        return parent::name($name === null ? null : "{$name}[value]");
    }

    public function currency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function locale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function decimals(?int $decimals): static
    {
        $this->decimals = $decimals;

        return $this;
    }

    public function decimalSeparator(?string $decimalSeparator): static
    {
        $this->decimalSeparator = $decimalSeparator;

        return $this;
    }

    public function groupSeparator(?string $groupSeparator): static
    {
        $this->groupSeparator = $groupSeparator;

        return $this;
    }

    public function showCurrency(bool $showCurrency = true): static
    {
        $this->showCurrency = $showCurrency;

        return $this;
    }

    public function clearable(bool $clearable = true): static
    {
        $this->clearable = $clearable;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            ...parent::hostAttributes(),
            'currency' => $this->currency,
            'locale' => $this->locale,
            'decimals' => $this->decimals,
            'decimal-separator' => $this->decimalSeparator,
            'group-separator' => $this->groupSeparator,
            'show-currency' => $this->showCurrency ? 'true' : 'false',
            'clearable' => $this->clearable ? 'true' : 'false',
        ];
    }

    #[\Override]
    protected function renderSlots(): string
    {
        $locale = $this->moneyName === null
            ? ''
            : Html::tag('input', '', [
                'type' => 'hidden',
                'name' => "{$this->moneyName}[locale]",
                'value' => $this->locale,
            ]);

        return $locale.parent::renderSlots();
    }
}
