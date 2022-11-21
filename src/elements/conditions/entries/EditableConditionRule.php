<?php

namespace craft\elements\conditions\entries;

use Craft;
use craft\base\conditions\BaseLightswitchConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;

/**
 * Entry editable condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
class EditableConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Editable');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $user = Craft::$app->getUser()->getIdentity();

        if (!$user || $user->admin) {
            $admin = $user?->admin;
            if ((!$admin && $this->value) || ($admin && !$this->value)) {
                $query->id(false);
            }
            return;
        }

        $fullySavableSections = [];
        $restrictedSections = [];

        foreach (Craft::$app->getSections()->getAllSections() as $section) {
            if ($user->can("savePeerEntries:$section->uid")) {
                $fullySavableSections[] = $section->id;
            } elseif ($user->can("saveEntries:$section->uid")) {
                $restrictedSections[] = $section->id;
            }
        }

        if (!$fullySavableSections && !$restrictedSections) {
            if ($this->value) {
                $query->id(false);
            }
            return;
        }

        $condition = [];

        if ($fullySavableSections) {
            $condition[] = ['entries.sectionId' => $fullySavableSections];
        }

        if ($restrictedSections) {
            $condition[] = [
                'entries.sectionId' => $restrictedSections,
                'entries.authorId' => $user->id,
            ];
        }

        if (count($condition) === 1) {
            $condition = reset($condition);
        } else {
            array_unshift($condition, 'or');
        }

        if (!$this->value) {
            $condition = ['not', $condition];
        }

        $query->andWhere($condition);
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        $savable = Craft::$app->getElements()->canSave($element);
        return $savable === $this->value;
    }
}
