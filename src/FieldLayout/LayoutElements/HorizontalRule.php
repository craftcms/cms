<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use craft\base\ElementInterface;
use craft\helpers\Cp;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\Support\Html;
use Override;

use function CraftCms\Cms\t;

/**
 * HorizontalRule represents an `<hr>` UI element can be included in field layouts.
 */
class HorizontalRule extends FieldLayoutElement
{
    /**
     * {@inheritdoc}
     */
    #[Override]
    public function isMultiInstance(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function selectorHtml(): string
    {
        $label = t('Horizontal Rule');
        $indicatorHtml = $this->hasConditions() ? Html::tag('div', Cp::iconSvg('diamond'), [
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

    /**
     * {@inheritdoc}
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Html::tag('hr');
    }
}
