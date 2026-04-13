<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

use function CraftCms\Cms\t;

class NotRelatedToConditionRule extends RelatedToConditionRule
{
    #[\Override]
    public function getLabel(): string
    {
        return t('Not Related To');
    }

    #[\Override]
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $elementIds = $this->getElementIds();

        if (! empty($elementIds)) {
            $query->andNotRelatedTo($elementIds);
        }
    }

    #[\Override]
    public function matchElement(ElementInterface $element): bool
    {
        $elementIds = $this->getElementIds();

        if (empty($elementIds)) {
            return true;
        }

        return $element::find()
            ->id($element->id ?: false)
            ->site('*')
            ->drafts($element->getIsDraft())
            ->provisionalDrafts($element->isProvisionalDraft)
            ->revisions($element->getIsRevision())
            ->status(null)
            ->notRelatedTo($elementIds)
            ->exists();
    }
}
