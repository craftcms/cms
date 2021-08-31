<?php

namespace craft\conditions\elements\entry;

use Craft;
use craft\conditions\BaseMultiSelectValueConditionRule;
use craft\conditions\BaseSelectValueConditionRule;
use craft\conditions\elements\ElementQueryConditionRuleInterface;
use craft\db\Table;
use craft\elements\db\EntryQuery;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use yii\db\QueryInterface;

/**
 *
 */
class AuthorGroupConditionRule extends BaseMultiSelectValueConditionRule implements ElementQueryConditionRuleInterface
{
    /**
     * @inheritDoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->value)) {
            $this->value = [];
        }
    }

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Author Group');
    }

    /**
     * @inheritDoc
     */
    public function getSelectOptions(): array
    {
        $sections = Craft::$app->getUserGroups()->getAllGroups();
        return ArrayHelper::map($sections, 'uid', 'name');
    }

    /**
     * @inheritDoc
     */
    public function modifyQuery(QueryInterface $query): QueryInterface
    {
        $ids = [];
        foreach ($this->value as $userGroupUid) {
            if ($id = Db::idByUid(Table::USERGROUPS, $userGroupUid)) {
                $ids[] = $id;
            }
        }
        /** @var EntryQuery $query */
        return $query->authorGroup($ids);
    }
}