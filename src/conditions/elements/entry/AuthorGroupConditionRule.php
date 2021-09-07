<?php

namespace craft\conditions\elements\entry;

use Craft;
use craft\conditions\BaseMultiSelectValueConditionRule;
use craft\conditions\elements\ElementQueryConditionRuleInterface;
use craft\elements\db\EntryQuery;
use craft\helpers\ArrayHelper;
use yii\db\QueryInterface;

/**
 * Author group condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AuthorGroupConditionRule extends BaseMultiSelectValueConditionRule implements ElementQueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->value)) {
            $this->value = [];
        }
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Author Group');
    }

    /**
     * @inheritdoc
     */
    public function getSelectOptions(): array
    {
        $sections = Craft::$app->getUserGroups()->getAllGroups();
        return ArrayHelper::map($sections, 'uid', 'name');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): QueryInterface
    {
        /** @var EntryQuery $query */
        $userGroupsService = Craft::$app->getUserGroups();
        $userGroups = array_filter(array_map(function(string $uid) use ($userGroupsService) {
            return $userGroupsService->getGroupByUid($uid);
        }, $this->value));
        return $query->authorGroup($userGroups);
    }
}
