<?php

namespace craft\conditions\elements;

use Craft;
use craft\conditions\BaseQueryCondition;
use craft\conditions\elements\fields\FieldConditionRuleInterface;
use craft\errors\InvalidTypeException;
use yii\base\InvalidArgumentException;

/**
 * ElementQueryCondition provides an element query condition.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ElementQueryCondition extends BaseQueryCondition
{
    /**
     * @var string The field context that should be used when fetching custom fields’ condition rule types.
     * @see conditionRuleTypes()
     */
    public string $fieldContext = 'global';

    /**
     * @inheritdoc
     */
    protected function addRuleLabel(): string
    {
        return Craft::t('app', 'Add a filter');
    }

    /**
     * @inheritdoc
     */
    protected function conditionRuleTypes(): array
    {
        $types = [
            RelatedToConditionRule::class,
            SlugConditionRule::class,
            TrashedConditionRule::class,
        ];

        foreach (Craft::$app->getFields()->getAllFields($this->fieldContext) as $field) {
            if (($class = $field->getQueryConditionRuleType()) !== null) {
                if (!is_subclass_of($class, FieldConditionRuleInterface::class)) {
                    throw new InvalidTypeException($class, FieldConditionRuleInterface::class);
                }

                $types[] = [
                    'class' => $class,
                    'fieldUid' => $field->uid,
                ];
            }
        }

        return $types;
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        $config = parent::getConfig();
        $config['fieldContext'] = $this->fieldContext;
        return $config;
    }
}
