<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Support\Html;

class ConditionRuleRenderer
{
    public function __construct(
        private readonly FormResolver $resolver,
        private readonly FormHtmlRenderer $renderer,
    ) {}

    public function render(ConditionRuleInterface $rule): string
    {
        return $this->renderForm($rule->getForm());
    }

    public function renderForm(Form $form): string
    {
        $payload = $this->resolver->resolve($form, new FormContext(refreshable: true));
        $html = '';

        foreach ($payload->nodes as $node) {
            $html .= Html::tag('div', $this->renderer->renderNodes([$node], $payload), [
                'class' => [
                    'condition-rule-field',
                    $node->control?->type === Choice::class ? 'shrink-0' : 'min-w-0',
                ],
            ]);
        }

        return Html::tag('div', $html, ['class' => 'condition-rule-fields flex flex-start gap-2']);
    }
}
