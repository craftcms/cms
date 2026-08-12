<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\Concerns;

use CraftCms\Cms\Cp\Enums\Appearance;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;

trait ElementCrumbs
{
    /** @return list<array<string, mixed>> */
    protected function crumbs(ElementInterface $element, bool $current = true): array
    {
        $crumbs = $element->isProvisionalDraft
            ? $element->getCanonical(true)->getCrumbs()
            : $element->getCrumbs();

        return [
            ...$crumbs,
            [
                'html' => app(ElementHtml::class)->elementChipHtml($element, [
                    'showDraftName' => ! $current,
                    'class' => 'chromeless',
                    'hyperlink' => true,
                    'appearance' => Appearance::Plain->value,
                ]),
            ],
        ];
    }
}
