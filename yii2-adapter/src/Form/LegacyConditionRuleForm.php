<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Form;

use CraftCms\Cms\Condition\BaseElementSelectConditionRule;
use CraftCms\Cms\Condition\BaseNumberConditionRule;
use CraftCms\Cms\Condition\BaseTextConditionRule;
use CraftCms\Cms\Condition\ConditionRuleRenderer;
use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Cp\RequestedSite;
use CraftCms\Cms\Form\Controls\ElementSelect;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;

class LegacyConditionRuleForm
{
    public function __construct(
        private readonly LegacyHtml $legacyHtml,
        private readonly ConditionRuleRenderer $renderer,
    ) {
    }

    /** @param callable(): string $html */
    public function capture(ConditionRuleInterface $rule, callable $html): Form
    {
        $namespace = InputNamespace::get();
        $node = InputNamespace::with(null, fn() => $this->legacyHtml->capture(
            path: ['__legacyConditionRule', $rule->uid],
            hook: fn(): string => InputNamespace::with($namespace, $html),
        ));

        return Form::make([$node]);
    }

    public function render(Form $form, string $inputHtml): string
    {
        return Html::tag('div', $this->renderer->renderForm($form) . $inputHtml, [
            'class' => 'flex flex-start',
        ]);
    }

    /** @param array<string, mixed> $options */
    public function textInput(BaseTextConditionRule $rule, Form $form, array $options): string
    {
        if ($rule->operator === 'between' || !array_any($form->nodes(), fn($node): bool => $node->getControl()?->path() === 'value')) {
            return $this->renderer->renderForm($form);
        }

        $input = match ($rule->operator) {
            'empty', 'notempty' => '',
            'in', 'ni' => FormFields::selectizeHtml($options),
            default => Html::hiddenLabel(Html::encode($rule->getLabel()), $options['id']) . FormFields::textHtml($options),
        };
        $additionalFields = array_filter($form->nodes(), fn($node): bool => $node->getControl()?->path() !== 'value');

        return $input . $this->renderer->renderForm(Form::make(array_values($additionalFields)));
    }

    /** @return array<string, mixed> */
    public function textOptions(BaseTextConditionRule $rule, string $inputType): array
    {
        $options = [
            'id' => 'value' . mt_rand(),
            'name' => 'value',
            'class' => 'flex-grow flex-shrink',
        ];

        if (in_array($rule->operator, ['in', 'ni'], true)) {
            $values = Json::decodeIfJson($rule->value);
            $values = is_array($values) ? array_values($values) : [];
            $options += [
                'values' => $values,
                'options' => array_map(fn($value): array => ['value' => $value, 'label' => $value], $values),
                'multi' => true,
                'allowEmptyOption' => true,
                'selectizeOptions' => ['create' => true, 'persist' => false, 'createOnBlur' => true],
            ];
        } else {
            $options += ['type' => $inputType, 'value' => $rule->value, 'autocomplete' => false];
        }

        if ($rule instanceof BaseNumberConditionRule) {
            $options['step'] = $rule->step ?? 'any';
        }

        return $options;
    }

    /** @param array<string, mixed> $options */
    public function elementInput(BaseElementSelectConditionRule $rule, Form $form, array $options): string
    {
        if ($rule->getCondition()->forProjectConfig || !array_any($form->nodes(), fn($node): bool => $node->getControl()?->path() === 'elementIds')) {
            return $this->renderer->renderForm($form);
        }

        $additionalFields = array_filter($form->nodes(), fn($node): bool => $node->getControl()?->path() !== 'elementIds');

        return $this->renderer->renderForm(Form::make(array_values($additionalFields))) . FormFields::elementSelectHtml($options);
    }

    /** @return array<string, mixed> */
    public function elementOptions(ElementSelect $control): array
    {
        $ids = $control->getValue();
        $props = $control->props($ids);
        $elementType = $props['elementType'];
        $elements = empty($ids) ? [] : $elementType::find()
            ->site('*')
            ->preferSites(array_filter([app(RequestedSite::class)->get()?->id]))
            ->unique()
            ->id($ids)
            ->status(null)
            ->limit($props['limit'])
            ->all();

        return [
            'name' => 'elementIds',
            'elements' => $elements,
            'elementType' => $elementType,
            'sources' => $props['sources'],
            'criteria' => $props['criteria'],
            'condition' => isset($props['selectionCondition']) ? app(Conditions::class)->createCondition($props['selectionCondition']) : null,
            'single' => $props['limit'] === 1,
            'showSiteMenu' => $props['showSiteMenu'],
        ];
    }
}
