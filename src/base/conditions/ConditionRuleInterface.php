<?php

namespace craft\base\conditions;

use craft\base\ComponentInterface;
use yii\base\InvalidConfigException;

/**
 * ConditionRuleInterface defines the common interface to be implemented by condition rule classes.
 *
 * A base implementation is provided by [[BaseConditionRule]].
 *
 * @property ConditionInterface $condition The condition associated with this rule
 * @property-read array $config The rule’s portable config
 * @property-read string $label The rule’s option label
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @mixin BaseConditionRule
 */
interface ConditionRuleInterface extends ComponentInterface
{
    /**
     * Returns whether the rule is safe to include in conditions that are stored in the project config.
     *
     * @return bool
     */
    public static function supportsProjectConfig(): bool;

    /**
     * Returns the rule’s option label.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Returns the optgroup label the condition rule should be grouped under.
     *
     * @return string|null
     */
    public function getGroupLabel(): ?string;

    /**
     * Returns the rule’s portable config.
     *
     * @return array
     * @throws InvalidConfigException if the rule is misconfigured
     */
    public function getConfig(): array;

    /**
     * Returns the rule’s HTML for a condition builder.
     *
     * @return string
     */
    public function getHtml(): string;

    /**
     * Sets the condition associated with this rule.
     *
     * @param ConditionInterface $condition
     */
    public function setCondition(ConditionInterface $condition): void;

    /**
     * Returns the condition associated with this rule.
     *
     * @return ConditionInterface
     */
    public function getCondition(): ConditionInterface;

    /**
     * Returns whether the rule’s type selector should be autofocused.
     *
     * @return bool
     */
    public function getAutofocus(): bool;

    /**
     * Sets whether the rule’s type selector should be autofocused.
     *
     * @param bool $autofocus
     */
    public function setAutofocus(bool $autofocus = true): void;
}
