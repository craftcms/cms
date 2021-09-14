<?php

namespace craft\conditions;

use craft\base\Component;
use craft\helpers\StringHelper;

/**
 *
 * @property ConditionInterface $condition
 * @property-read array $config
 * @property-read string|null $html
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseConditionRule extends Component implements ConditionRuleInterface
{
    /**
     * @var string UUID
     */
    public string $uid;

    /**
     * @var ConditionInterface
     */
    private ConditionInterface $_condition;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->uid)) {
            $this->uid = StringHelper::UUID();
        }
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'type' => get_class($this),
            'uid' => $this->uid,
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'conditionRules',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        return array_merge(parent::fields(), [
            'type' => fn() => get_class($this),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function setCondition(ConditionInterface $condition): void
    {
        $this->_condition = $condition;
    }

    /**
     * @inheritdoc
     */
    public function getCondition(): ConditionInterface
    {
        return $this->_condition;
    }

    /**
     * @inheritdoc
     */
    abstract public function getHtml(array $options = []): string;

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['uid'], 'safe'],
        ];
    }

    protected function getRuleBodyId()
    {
        return "#" . \Craft::$app->getView()->namespaceInputId('rule-body', \Craft::$app->getView()->getNamespace());
    }
}
