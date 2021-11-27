<?php

namespace craft\conditions\elements\entries;

use Craft;
use craft\conditions\BaseElementSelectConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\EntryQuery;
use craft\elements\User;
use yii\db\QueryInterface;

/**
 * Author condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AuthorConditionRule extends BaseElementSelectConditionRule implements QueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Author');
    }

    /**
     * @inheritdoc
     */
    protected function elementType(): string
    {
        return User::class;
    }

    /**
     * @inheritdoc
     */
    protected function criteria(): ?array
    {
        return [
            'authors' => true,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['author', 'authorId'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var EntryQuery $query */
        $query->authorId($this->getElementId());
    }
}
