<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;

/**
 * @since 6.0.0
 */
class ConditionRuleRenderer
{
    public function __construct(
        private readonly UiResolver $resolver,
        private readonly UiHtmlRenderer $renderer,
    ) {}

    public function render(ConditionRuleInterface $rule): string
    {
        return $this->renderUi($rule->getUi());
    }

    public function renderUi(Ui $ui): string
    {
        $payload = $this->resolver->resolve($ui, new UiContext(refreshable: true));
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
