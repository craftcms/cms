<?php

namespace craft\fields\conditions;

use craft\base\conditions\BaseConditionRule;
use craft\elements\db\ElementQueryInterface;
use Illuminate\Support\Collection;
use yii\base\InvalidConfigException;

/**
 * Empty/not-empty field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.2.0
 */
class EmptyFieldConditionRule extends BaseConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    /**
     * @inheritdoc
     */
    public string $operator = self::OPERATOR_NOT_EMPTY;

    /**
     * @inheritdoc
     */
    public static function supportsProjectConfig(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function operators(): array
    {
        return [
            self::OPERATOR_NOT_EMPTY,
            self::OPERATOR_EMPTY,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function elementQueryParam(): int|string|null
    {
        return match ($this->operator) {
            self::OPERATOR_EMPTY => ':empty:',
            self::OPERATOR_NOT_EMPTY => 'not :empty:',
            default => throw new InvalidConfigException("Invalid operator: $this->operator"),
        };
    }

    /**
     * @inheritdoc
     */
    protected function matchFieldValue($value): bool
    {
        /** @var ElementQueryInterface|Collection $value */
        if ($value instanceof ElementQueryInterface) {
            $isEmpty = !$value->exists();
        } else {
            $isEmpty = $value->isEmpty();
        }

        if ($this->operator === self::OPERATOR_EMPTY) {
            return $isEmpty;
        }

        return !$isEmpty;
    }
}
