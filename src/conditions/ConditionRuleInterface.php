<?php

namespace craft\conditions;

use craft\base\ComponentInterface;

/**
 * ConditionRuleInterface defines the common interface to be implemented by condition rule classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface ConditionRuleInterface extends ComponentInterface
{
    /**
     * @return array
     */
    public function getConfig(): array;

    /**
     * @param array $options The condition builder options
     * @return string
     */
    public function getHtml(array $options = []): string;

    /**
     * @param ConditionInterface $condition
     */
    public function setCondition(ConditionInterface $condition): void;

    /**
     * @return ConditionInterface
     */
    public function getCondition(): ConditionInterface;

    /**
     * Validates a given condition to ensure it can be used with this rule.
     *
     * @param ConditionInterface $condition
     * @return bool
     */
    public function validateCondition(ConditionInterface $condition): bool;
}
