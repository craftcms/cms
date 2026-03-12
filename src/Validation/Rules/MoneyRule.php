<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Rules;

use Closure;
use craft\base\ElementInterface;
use CraftCms\Cms\Support\Facades\I18N;
use Illuminate\Contracts\Validation\ValidationRule;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use NumberFormatter;

use function CraftCms\Cms\t;

readonly class MoneyRule implements ValidationRule
{
    public function __construct(
        private ElementInterface $model,
        private int|null|float $min = null,
        private int|null|float $max = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $currencies = new ISOCurrencies;
        $numberFormatter = new NumberFormatter(I18N::getFormattingLocale()->id, NumberFormatter::CURRENCY);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);

        if (! $value instanceof Money) {
            $currency = ! $value['currency'] instanceof Currency ? new Currency($value['currency']) : $value['currency'];
            $value = new Money($value['value'], $currency);
        }

        if ($this->max !== null) {
            $max = new Money($this->max, $value->getCurrency());

            if ($value->greaterThan($max)) {
                $fail(t('{attribute} must be no greater than {max}.', [
                    'attribute' => $this->model->getAttributeLabel($attribute),
                    'max' => $moneyFormatter->format($max),
                ]));
            }
        }

        if ($this->min !== null) {
            $min = new Money($this->min, $value->getCurrency());

            if ($value->lessThan($min)) {
                $fail(t('{attribute} must be no less than {min}.', [
                    'attribute' => $this->model->getAttributeLabel($attribute),
                    'min' => $moneyFormatter->format($min),
                ]));
            }
        }
    }
}
