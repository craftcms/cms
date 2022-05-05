<?php

namespace craft\elements\conditions\entries;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\models\UserGroup;

/**
 * Author group condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AuthorGroupConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Author Group');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['authorGroup', 'authorGroupId'];
    }

    /**
     * @inheritdoc
     */
    public static function isSelectable(): bool
    {
        return !empty(Craft::$app->getUserGroups()->getAllGroups());
    }

    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        $sections = Craft::$app->getUserGroups()->getAllGroups();
        return ArrayHelper::map($sections, 'uid', 'name');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var EntryQuery $query */
        $userGroups = Craft::$app->getUserGroups();
        $query->authorGroupId($this->paramValue(fn($uid) => $userGroups->getGroupByUid($uid)->id ?? null));
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Entry $element */
        $groupUids = array_map(fn(UserGroup $group) => $group->uid, $element->getAuthor()->getGroups());
        return $this->matchValue($groupUids);
    }
}
