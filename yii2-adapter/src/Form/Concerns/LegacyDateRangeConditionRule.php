<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Form\Concerns;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Shared\Enums\DateRangeType;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use function CraftCms\Cms\t;

/** @phpstan-require-extends \CraftCms\Cms\Condition\BaseDateRangeConditionRule */
trait LegacyDateRangeConditionRule
{
    use LegacyConditionRuleForm;

    /**
     * @noinspection PhpNamedArgumentsWithChangedOrderInspection
     */
    protected function inputHtml(): string
    {
        $groupedOptions = [];

        foreach ($this->rangeTypeOptions() as $value => $label) {
            if (in_array($value, [
                DateRangeType::Before->value,
                DateRangeType::After->value,
                DateRangeType::Range->value,
            ])) {
                $index = 1;
            } elseif (in_array($value, [self::OPERATOR_NOT_EMPTY, self::OPERATOR_EMPTY])) {
                $index = 2;
            } else {
                $index = 0;
            }

            $groupedOptions[$index][] = Html::beginTag('li') .
                Html::a($label, options: [
                    'class' => $value === $this->rangeType ? 'sel' : false,
                    'data' => ['value' => $value],
                ]) .
                Html::endTag('li');
        }

        $optionLists = [];

        foreach ($groupedOptions as $options) {
            $optionLists[] = Html::tag('ul', implode('', $options), ['class' => 'padded']);
        }

        $rangeTypeOptionsHtml = implode(Html::tag('hr', attributes: ['class' => 'padded']), $optionLists);

        $buttonId = 'date-range-btn';
        $inputId = 'date-range-input';
        $menuId = 'date-range-menu';

        HtmlStack::jsWithVars(
            fn($buttonId, $inputId) => <<<JS
    Garnish.requestAnimationFrame(() => {
      const \$button = $('#' + $buttonId);
      \$button.menubtn().data('menubtn').on('optionSelect', event => {
    const \$option = $(event.option);
    \$button.text(\$option.text()).removeClass('add');
    // Don't use data('value') here because it could result in an object if data-value is JSON
    const \$input = $('#' + $inputId).val(\$option.attr('data-value'));
    \$input[0].dispatchEvent(new Event('change', {bubbles: true}));
      });
    });
    JS,
            [
                InputNamespace::namespaceId($buttonId),
                InputNamespace::namespaceId($inputId),
            ]
        );

        $html = Html::button($this->rangeTypeOptions()[$this->rangeType], [
            'id' => $buttonId,
            'class' => ['btn', 'menubtn'],
            'autofocus' => false,
            'aria' => [
                'label' => t('Date Range'),
            ],
        ]) .
            Html::tag('div', $rangeTypeOptionsHtml, [
                'id' => $menuId,
                'class' => 'menu',
            ]) .
            Html::hiddenInput('rangeType', $this->rangeType, [
                'id' => $inputId,
            ]);

        if ($this->rangeType === DateRangeType::Range->value) {
            $html .= Html::tag(
                'div',
                attributes: ['class' => ['flex', 'flex-nowrap']],
                content: Html::label(t('From'), 'start-date-date') .
                Html::tag('div',
                    FormFields::dateHtml([
                        'id' => 'start-date',
                        'name' => 'startDate',
                        'value' => $this->getStartDate(),
                    ])
                )
            ) .
                Html::tag(
                    'div',
                    attributes: ['class' => ['flex', 'flex-nowrap']],
                    content: Html::label(t('To'), 'end-date-date') .
                    Html::tag('div',
                        FormFields::dateHtml([
                            'id' => 'end-date',
                            'name' => 'endDate',
                            'value' => $this->getEndDate(),
                        ])
                    )
                );
        } elseif (in_array($this->rangeType, [DateRangeType::Before->value, DateRangeType::After->value])) {
            $periodValueId = 'period-value';
            $periodTypeId = 'period-type';

            $html .= Html::hiddenLabel(t('Period Value'), $periodValueId) .
                Html::tag(
                    'div',
                    attributes: ['class' => ['flex', 'flex-nowrap']],
                    content: FormFields::textHtml([
                        'id' => $periodValueId,
                        'name' => 'periodValue',
                        'value' => $this->periodValue,
                        'size' => '5',
                    ]) .
                    Html::hiddenLabel(t('Period Type'), $periodTypeId) .
                    FormFields::selectHtml([
                        'id' => $periodTypeId,
                        'name' => 'periodType',
                        'value' => $this->periodType,
                        'options' => $this->periodTypeOptions(),
                    ])
                );
        }

        return Html::tag('div', $html, ['class' => ['flex']]);
    }
}
