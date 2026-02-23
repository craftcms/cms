<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Conditions;

use craft\base\ElementInterface;
use craft\helpers\Cp;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Condition\BaseNumberConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Html;
use Illuminate\Validation\Rule;
use yii\base\InvalidValueException;

use function CraftCms\Cms\t;

final class FileSizeConditionRule extends BaseNumberConditionRule implements ElementConditionRuleInterface
{
    public const string UNIT_B = 'B';

    public const string UNIT_KB = 'KB';

    public const string UNIT_MB = 'MB';

    public const string UNIT_GB = 'GB';

    /**
     * @var string The size unit
     *
     * @phpstan-var self::UNIT_B|self::UNIT_KB|self::UNIT_MB|self::UNIT_GB
     */
    public string $unit = self::UNIT_B;

    public function getLabel(): string
    {
        return t('File Size');
    }

    #[\Override]
    protected function inputHtml(): string
    {
        $unitId = 'unit';

        return Html::tag('div',
            parent::inputHtml().
            Html::hiddenLabel(t('Unit'), $unitId).
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

    public function getExclusiveQueryParams(): array
    {
        return ['size'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AssetQuery $query */
        if ($this->unit === self::UNIT_B) {
            $query->size($this->paramValue());

            return;
        }

        if (! $this->value) {
            return;
        }

        [$minBytes, $maxBytes] = $this->_byteRange();

        match ($this->operator) {
            self::OPERATOR_EQ => $query->size(['and', ">= $minBytes", "<= $maxBytes"]),
            self::OPERATOR_NE => $query->size(['or', "< $minBytes", "> $maxBytes"]),
            self::OPERATOR_LT => $query->size("< $minBytes"),
            self::OPERATOR_LTE => $query->size("<= $maxBytes"),
            self::OPERATOR_GT => $query->size("> $maxBytes"),
            self::OPERATOR_GTE => $query->size(">= $minBytes"),
            default => throw new InvalidValueException("Invalid file size operator: $this->operator"),
        };
    }

    public function matchElement(ElementInterface $element): bool
    {
        if (! $this->value) {
            return true;
        }

        /** @var Asset $element */
        if (! $element->size) {
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

    #[\Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'unit' => ['required', 'string', Rule::in([
                self::UNIT_B,
                self::UNIT_KB,
                self::UNIT_MB,
                self::UNIT_GB,
            ])],
        ]);
    }

    /**
     * Returns the min and max bytes that [[value]] should actually represent, when the actual value is rounded to [[unit]].
     *
     * @return array<int,int>
     */
    private function _byteRange(): array
    {
        if ($this->unit === self::UNIT_B) {
            return [(int) $this->value, (int) $this->value];
        }

        $multiplier = 1;

        $multiplier *= match ($this->unit) {
            self::UNIT_GB => 1000 * 1000 * 1000,
            self::UNIT_MB => 1000 * 1000,
            self::UNIT_KB => 1000,
            default => throw new InvalidValueException("Invalid file size unit: $this->unit"),
        };

        // 1 KB == 500 - 1,499 B
        $maxDiff = $multiplier / 2;
        $minBytes = (int) $this->value * $multiplier - $maxDiff;
        $maxBytes = (int) $this->value * $multiplier + $maxDiff - 1;

        return [$minBytes, $maxBytes];
    }

    #[\Override]
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'unit' => $this->unit,
        ]);
    }
}
