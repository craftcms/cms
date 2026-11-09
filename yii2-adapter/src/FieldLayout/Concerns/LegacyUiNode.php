<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\FieldLayout\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Yii2Adapter\FieldLayout\LegacyLayoutElementHtml;

/** @phpstan-require-extends FieldLayoutElement */
trait LegacyUiNode
{
    abstract public function formHtml(?ElementInterface $element = null, bool $static = false): ?string;

    public function uiNode(FieldLayoutElementContext $context): ?Node
    {
        return app(LegacyLayoutElementHtml::class)->capture($this, $context, $this->formHtml(...));
    }
}
