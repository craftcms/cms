<?php

namespace craft\elements\conditions\assets;

use Craft;
use craft\base\conditions\BaseNumberConditionRule;
use craft\base\ElementInterface;
use craft\elements\Asset;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\AssetQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Cp;
use craft\helpers\Html;
use yii\base\InvalidValueException;

/**
 * File Size condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class FileSizeConditionRule extends BaseNumberConditionRule implements ElementConditionRuleInterface
{
    public const UNIT_B = 'B';
    public const UNIT_KB = 'KB';
    public const UNIT_MB = 'MB';
    public const UNIT_GB = 'GB';

    /**
     * @var string The size unit
     * @phpstan-var self::UNIT_B|self::UNIT_KB|self::UNIT_MB|self::UNIT_GB
     */
    public string $unit = self::UNIT_B;

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'File Size');
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        $unitId = 'unit';
        return Html::tag('div',
            parent::inputHtml() .
            Html::hiddenLabel(Craft::t('app', 'Unit'), $unitId) .
            Cp::selectHtml([
                'name' => 'unit',
                'id' => $unitId,
                'options' => [
                    ['value' => self::UNIT_B, 'label' => self::UNIT_B],
                    ['value' => self::UNIT_KB, 'label' => self::UNIT_KB],
                    ['value' => self::UNIT_MB, 'label' => self::UNIT_MB],
                    ['value' => self::UNIT_GB, 'label' => self::UNIT_GB],
                ],
                'value' => $this->unit,
            ]),
            [
                'class' => ['flex', 'flex-nowrap'],
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['size'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AssetQuery $query */
        if ($this->unit === self::UNIT_B) {
            $query->size($this->paramValue());
            return;
        }

        if (!$this->value) {
            return;
        }

        [$minBytes, $maxBytes] = $this->_byteRange();

        switch ($this->operator) {
            case self::OPERATOR_EQ:
                $query->size(['and', ">= $minBytes", "<= $maxBytes"]);
                return;
            case self::OPERATOR_NE:
                $query->size(['or', "< $minBytes", "> $maxBytes"]);
                return;
            case self::OPERATOR_LT:
                $query->size("< $minBytes");
                return;
            case self::OPERATOR_LTE:
                $query->size("<= $maxBytes");
                return;
            case self::OPERATOR_GT:
                $query->size("> $maxBytes");
                return;
            case self::OPERATOR_GTE:
                $query->size(">= $minBytes");
                return;
            default:
                throw new InvalidValueException("Invalid file size operator: $this->operator");
        }
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        if (!$this->value) {
            return true;
        }

        /** @var Asset $element */
        if (!$element->size) {
            return false;
        }

        if ($this->unit === self::UNIT_B) {
            return $this->matchValue($this->value);
        }

        [$minBytes, $maxBytes] = $this->_byteRange();

        return match ($this->operator) {
            self::OPERATOR_EQ => $element->size >= $minBytes && $element->size <= $maxBytes,
            self::OPERATOR_NE => $element->size < $minBytes || $element->size > $maxBytes,
            self::OPERATOR_LT => $element->size < $minBytes,
            self::OPERATOR_LTE => $element->size <= $minBytes,
            self::OPERATOR_GT => $element->size > $maxBytes,
            self::OPERATOR_GTE => $element->size >= $maxBytes,
            default => throw new InvalidValueException("Invalid file size operator: $this->operator"),
        };
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [
            ['unit'], 'in', 'range' => [
                self::UNIT_B,
                self::UNIT_KB,
                self::UNIT_MB,
                self::UNIT_GB,
            ],
        ];
        return $rules;
    }

    /**
     * Returns the min and max bytes that [[value]] should actually represent, when the actual value is rounded to [[unit]].
     *
     * @return array
     * @phpstan-return array<int,int>
     */
    private function _byteRange(): array
    {
        if ($this->unit === self::UNIT_B) {
            return [(int)$this->value, (int)$this->value];
        }

        $multiplier = 1;

        switch ($this->unit) {
            case self::UNIT_GB:
                $multiplier *= 1000;
            // no break
            case self::UNIT_MB:
                $multiplier *= 1000;
            // no break
            case self::UNIT_KB:
                $multiplier *= 1000;
                break;
            default:
                throw new InvalidValueException("Invalid file size unit: $this->unit");
        }

        // 1 KB == 500 - 1,499 B
        $maxDiff = $multiplier / 2;
        $minBytes = (int)$this->value * $multiplier - $maxDiff;
        $maxBytes = (int)$this->value * $multiplier + $maxDiff - 1;

        return [$minBytes, $maxBytes];
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'unit' => $this->unit,
        ]);
    }
}
