<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Support\Facades\Sites;

use function CraftCms\Cms\t;

trait EditsElement
{
    protected readonly ElementRequest $request;

    /** @return array{string, string} */
    protected function editElementTitles(ElementInterface $element): array
    {
        $title = match (true) {
            $element::hasTitles() && $element->title !== null && $element->title !== '' => $element->title,
            ! $element->id || $element->getIsUnpublishedDraft() => t('Create a new {type}', [
                'type' => $element::lowerDisplayName(),
            ]),
            default => $element->getUiLabel(),
        };

        $docTitle = $element->getUiLabel();

        if ($element->getIsDraft() && ! $element->getIsUnpublishedDraft()) {
            if ($element->isProvisionalDraft) {
                $docTitle .= ' — '.t('Edited');
            } else {
                $docTitle .= " ($element->draftName)";
            }
        } elseif ($element->getIsRevision()) {
            $docTitle .= ' ('.$element->getRevisionLabel().')';
        }

        // Include site name if localized
        if ($element::isLocalized() && Sites::isMultiSite()) {
            $docTitle .= sprintf(' - %s', $element->getSite()->getUiLabel());
        }

        return [$docTitle, $title];
    }
}
