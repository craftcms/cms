<?php

namespace craft\elements\conditions;

use Craft;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;

/**
 * Not Relation condition rule.
 *
 * @property int[] $elementIds
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.4.0
 */
class NotRelatedToConditionRule extends RelatedToConditionRule
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Not Related To');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $elementId = $this->getElementId();
        if ($elementId !== null) {
            $query->andNotRelatedTo($elementId);
        }
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        $elementId = $this->getElementId();
        if (!$elementId) {
            return true;
        }

        return $element::find()
            ->id($element->id ?: false)
            ->site('*')
            ->drafts($element->getIsDraft())
            ->provisionalDrafts($element->isProvisionalDraft)
            ->revisions($element->getIsRevision())
            ->status(null)
            ->notRelatedTo($elementId)
            ->exists();
    }
}
