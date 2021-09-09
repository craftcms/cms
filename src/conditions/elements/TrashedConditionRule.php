<?php

namespace craft\conditions\elements;

use Craft;
use craft\conditions\BaseLightswitchConditionRule;
use craft\conditions\BaseSelectOperatorConditionRule;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use yii\db\QueryInterface;

/**
 * Element trashed condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class TrashedConditionRule extends BaseLightswitchConditionRule implements ElementQueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Trashed');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var ElementQuery $query */
        $query->trashed((bool)$this->value);
    }
}
