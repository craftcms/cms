<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Control;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Money as MoneyControl;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Translation\Locale;
use Illuminate\Validation\Rule;
use Money\Currencies\ISOCurrencies;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Money extends Number
{
    public string $currency = 'USD';

    public bool $showCurrency = true;

    public static function displayName(): string
    {
        return t('Money');
    }

    protected function createControl(TableCellContext $context): Control
    {
        return MoneyControl::make($context->path)
            ->currency($this->currency)
            ->showCurrency($this->showCurrency);
    }

    /** @return array{value: string|null, locale: string} */
    protected function controlValue(TableCellContext $context): array
    {
        $locale = I18N::getFormattingLocale();
        $value = $this->serializeValue($context->value);

        return [
            'value' => $value === null || $value === '' ? null : str_replace('.', $locale->getNumberSymbol(Locale::SYMBOL_DECIMAL_SEPARATOR), (string) $value),
            'locale' => $locale->id,
        ];
    }

    public function normalizeValue(mixed $value, bool $fromRequest = false): mixed
    {
        if (is_array($value)) {
            $value = I18N::normalizeNumber($value['value'] ?? null, $value['locale'] ?? null);
        }

        return $value === '' ? null : parent::normalizeValue($value, $fromRequest);
    }

    public function getValueRules(): array
    {
        return ['nullable', 'numeric'];
    }

    public function getRules(): array
    {
        return [
            ...parent::getRules(),
            'currency' => ['required', Rule::in(array_column(self::currencyOptions(), 'value'))],
            'showCurrency' => ['boolean'],
        ];
    }

    public function settingsForm(FormContext $context = new FormContext): ?Form
    {
        return Form::make([
            Field::make(t('Currency'))->required()
                ->control(Choice::make('currency')->options(self::currencyOptions())->value($this->currency)),
            Field::make(t('Show Currency'))
                ->control(Lightswitch::make('showCurrency')->value($this->showCurrency)),
        ]);
    }

    /** @return list<array{label: string, value: string}> */
    private static function currencyOptions(): array
    {
        $options = [];
        foreach (new ISOCurrencies as $currency) {
            $options[] = ['label' => $currency->getCode(), 'value' => $currency->getCode()];
        }

        return $options;
    }
}
