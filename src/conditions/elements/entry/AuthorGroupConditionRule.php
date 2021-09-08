<?php

namespace craft\conditions\elements\entry;

use Craft;
use craft\conditions\BaseMultiSelectOperatorConditionRule;
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
class AuthorGroupConditionRule extends BaseMultiSelectOperatorConditionRule implements ElementQueryConditionRuleInterface
{
    public array $authorGroups;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->_id = 'author-groups';

        if (!isset($this->authorGroups)) {
            $this->authorGroups = [];
        }
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'authorGroups',
        ]);
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
    public function modifyQuery(QueryInterface $query): void
    {
        $value = $this->value ?? false;

        /** @var EntryQuery $query */
        $userGroupsService = Craft::$app->getUserGroups();
        $userGroups = array_filter(array_map(static function(string $uid) use ($userGroupsService) {
            return $userGroupsService->getGroupByUid($uid);
        }, $value));

        $query->authorGroup($userGroups);
    }
}
