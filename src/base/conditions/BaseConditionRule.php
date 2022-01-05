<?php

namespace craft\base\conditions;

use craft\base\Component;
use craft\helpers\StringHelper;

/**
 * BaseConditionRule provides a base implementation for condition rules.
 *
 * @property bool $isNew Whether the rule is new
 * @property ConditionInterface $condition
 * @property-read array $config The rule’s portable config
 * @property-read string $html The rule’s HTML for a condition builder
 * @property-read string $uiLabel The rule’s option label
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseConditionRule extends Component implements ConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public static function supportsProjectConfig(): bool
    {
        return true;
    }

    /**
     * @var string UUID
     */
    public string $uid;

    /**
     * @var ConditionInterface
     */
    private ConditionInterface $_condition;

    /**
     * @var bool
     * @see getAutofocus()
     * @see setAutofocus()
     */
    private bool $_autofocus = false;

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
     * @inheritdoc
     */
    public function getCondition(): ConditionInterface
    {
        return $this->_condition;
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
    public function getConfig(): array
    {
        return [
            'class' => get_class($this),
            'uid' => $this->uid,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['uid'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAutofocus(): bool
    {
        return $this->_autofocus;
    }

    /**
     * @inheritdoc
     */
    public function setAutofocus(bool $autofocus = true): void
    {
        $this->_autofocus = $autofocus;
    }
}
