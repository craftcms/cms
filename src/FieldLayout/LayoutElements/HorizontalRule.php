<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use craft\base\ElementInterface;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\Support\Html;
use Override;

use function CraftCms\Cms\t;

/**
 * HorizontalRule represents an `<hr>` UI element can be included in field layouts.
 */
class HorizontalRule extends FieldLayoutElement
{
    #[Override]
    public function isMultiInstance(): bool
    {
        return true;
    }

    public function selectorHtml(): string
    {
        $label = t('Horizontal Rule');
        $indicatorHtml = $this->hasConditions() ? Html::tag('div', Icons::svg('diamond'), [
            'class' => ['cp-icon', 'puny', 'orange'],
            'title' => t('This element is conditional'),
            'aria' => ['label' => t('This element is conditional')],
        ]) : '';

        return <<<HTML
<div>
  <div class="fld-hr">
    <div class="smalltext light flex flex-nowrap gap-xs">
      <span>$label</span>
      $indicatorHtml
    </div>
  </div>
</div>
HTML;
    }

    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Html::tag('hr');
    }
}
