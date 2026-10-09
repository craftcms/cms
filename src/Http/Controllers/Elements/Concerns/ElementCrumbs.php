<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\Concerns;

use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Cp\Enums\Appearance;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;

trait ElementCrumbs
{
    /**
     * The element's crumbs, ending in a chip for it. The chip only links when
     * another crumb follows it; otherwise it names the page you're on.
     *
     * @return list<ActionItem|array<string, mixed>>
     */
    protected function crumbs(ElementInterface $element, bool $hyperlink = true): array
    {
        $crumbs = $element->isProvisionalDraft
            ? $element->getCanonical(true)->getCrumbs()
            : $element->getCrumbs();

        return [
            ...$crumbs,
            new ActionItem()->html(app(ElementHtml::class)->elementChipHtml($element->getCanonical(true), [
                'showDraftName' => false,
                'hyperlink' => $hyperlink,
                'appearance' => Appearance::Plain->value,
            ])),
        ];
    }
}
