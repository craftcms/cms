<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Form\Concerns;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Yii2Adapter\Form\LegacyConditionClasses;
use CraftCms\Yii2Adapter\Form\LegacyHtml;
use function CraftCms\Cms\t;

/** @phpstan-require-extends \CraftCms\Cms\Condition\BaseConditionRule */
trait LegacyConditionRuleForm
{
    public function getForm(FormContext $context = new FormContext()): Form
    {
        $namespace = LegacyHtml::namespace($context->namespace) ?? InputNamespace::get();
        $node = InputNamespace::with(null, fn() => app(LegacyHtml::class)->capture(
            path: ['__legacyConditionRule', $this->uid],
            hook: fn(): string => InputNamespace::with($namespace, $this->getHtml(...)),
            namespace: LegacyHtml::namespace($context->namespace),
        ));

        $node?->getControl()->deltaGroupAtNamespace()->expandValues()->reactive();

        return Form::make($node === null ? [] : [$node]);
    }

    public function getConfig(): array
    {
        $config = parent::getConfig();
        $config['class'] = LegacyConditionClasses::CORE_CLASSES[$config['class']] ?? $config['class'];

        return $config;
    }

    public function getHtml(): string
    {
        $operators = $this->operators();

        return
            Html::beginTag('div', [
                'class' => ['flex', 'flex-start'],
            ]) .
            (count($operators) > 1
                ? (
                    Html::hiddenLabel(t('Operator'), 'operator') .
                    FormFields::selectHtml([
                        'id' => 'operator',
                        'name' => 'operator',
                        'value' => $this->operator,
                        'options' => array_map(fn($operator) => ['value' => $operator, 'label' => $this->operatorLabel($operator)], $operators),
                    ])
                )
                : Html::hiddenInput('operator', reset($operators))
            ) .
            $this->inputHtml() .
            Html::endTag('div');
    }

    /**
     * Returns the input HTML.
     */
    protected function inputHtml(): string
    {
        return '';
    }
}
