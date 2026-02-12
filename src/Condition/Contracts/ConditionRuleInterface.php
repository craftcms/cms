<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition\Contracts;

use craft\base\ModelInterface;
use CraftCms\Cms\Component\Contracts\ComponentInterface;
use CraftCms\Cms\Condition\BaseConditionRule;
use yii\base\InvalidConfigException;

/**
 * ConditionRuleInterface defines the common interface to be implemented by condition rule classes.
 *
 * A base implementation is provided by [[BaseConditionRule]].
 *
 * @property ConditionInterface $condition The condition associated with this rule
 * @property-read array $config The rule’s portable config
 * @property-read string $label The rule’s option label
 *
 * @mixin BaseConditionRule
 *
 * @phpstan-require-extends BaseConditionRule
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 */
interface ConditionRuleInterface extends ComponentInterface, ModelInterface
{
    /**
     * Returns whether the rule is safe to include in conditions that are stored in the project config.
     */
    public static function supportsProjectConfig(): bool;

    /**
     * Returns the rule’s option label.
     */
    public function getLabel(): string;

    /**
     * Returns the rule’s option label hint.
     *
     * @since 4.6.0
     */
    public function getLabelHint(): ?string;

    /**
     * Returns whether to show rule’s option label hint.
     *
     * @since 4.16.0
     */
    public function showLabelHint(): bool;

    /**
     * Returns the optgroup label the condition rule should be grouped under.
     */
    public function getGroupLabel(): ?string;

    /**
     * Returns the rule’s portable config.
     *
     * @throws InvalidConfigException if the rule is misconfigured
     */
    public function getConfig(): array;

    /**
     * Returns the rule’s HTML for a condition builder.
     */
    public function getHtml(): string;

    /**
     * Sets the condition associated with this rule.
     */
    public function setCondition(ConditionInterface $condition): void;

    /**
     * Returns the condition associated with this rule.
     */
    public function getCondition(): ConditionInterface;

    /**
     * Returns whether the rule’s type selector should be autofocused.
     */
    public function getAutofocus(): bool;

    /**
     * Sets whether the rule’s type selector should be autofocused.
     */
    public function setAutofocus(bool $autofocus = true): void;
}
