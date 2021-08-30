<?php

namespace craft\conditions;

use craft\base\Component;
use craft\helpers\StringHelper;
use Illuminate\Support\Collection;

/**
 *
 * @property BaseCondition $condition
 * @property-read array $config
 * @property-read string|null $html
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0
 */
abstract class BaseConditionRule extends Component implements ConditionRuleInterface
{
    /**
     * @var string
     */
    public string $uid;

    /**
     * @var ConditionInterface
     */
    private ConditionInterface $_condition;

    /**
     * @inheritDoc
     */
    public function init(): void
    {
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
     * @inheritDoc
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes[] = 'conditionRules';

        return $attributes;
    }

    /**
     * @inheritDoc
     */
    public function fields(): array
    {
        $fields = parent::fields();
        $fields['type'] = function(): ?string {
            return get_class($this);
        };
        return $fields;
    }

    /**
     * @param ConditionInterface $condition
     */
    public function setCondition(ConditionInterface $condition): void
    {
        $this->_condition = $condition;
    }

    /**
     * @return BaseCondition
     */
    public function getCondition(): BaseCondition
    {
        return $this->_condition;
    }

    /**
     * @inheritDoc
     */
    public function getHtml(): string
    {
        return $this->getInputHtml();
    }

    /**
     * @inheritDoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = ['uid', 'safe'];

        return $rules;
    }
}