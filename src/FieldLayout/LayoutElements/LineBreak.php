<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Nodes\LineBreak as LineBreakNode;
use CraftCms\Cms\Support\Html;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

/**
 * LineBreak represents a line break UI element can be included in field layouts.
 */
class LineBreak extends FieldLayoutElement
{
    public static function make(): static
    {
        return app(static::class);
    }

    #[Override]
    public function isMultiInstance(): bool
    {
        return true;
    }

    public function selectorHtml(): string
    {
        $label = t('Line Break');
        $indicatorHtml = $this->hasConditions() ? Html::tag('div', Icons::svg('diamond'), [
            'class' => ['cp-icon', 'puny', 'orange'],
            'title' => t('This element is conditional'),
            'aria' => ['label' => t('This element is conditional')],
        ]) : '';

        return <<<HTML
<div>
  <div class="fld-br">
    <div class="fld-br__label">
      <span>$label</span>
      $indicatorHtml
    </div>
  </div>
</div>
HTML;
    }

    #[Override]
    public function formNode(FieldLayoutElementContext $context): ?Node
    {
        if (! $this->uid) {
            throw new InvalidArgumentException('Persisted Line Break FieldLayout elements require stable UIDs.');
        }

        return LineBreakNode::make($this->uid);
    }
}
