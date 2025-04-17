<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\InlineEditableFieldInterface;
use craft\base\MergeableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\elements\Entry;
use craft\fields\conditions\NumberFieldConditionRule;
use craft\gql\types\Number as NumberType;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Localization;
use GraphQL\Type\Definition\Type;
use yii\db\Schema;

/**
 * Range represents a Range field, which provides a tactile UI around a numeric value.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.5.0
 */
class Range extends Field implements InlineEditableFieldInterface, SortableFieldInterface, MergeableFieldInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Range');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'slider';
    }

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return 'int|null';
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): string
    {
        return Schema::TYPE_INTEGER;
    }

    /**
     * @inheritdoc
     */
    public static function queryCondition(array $instances, mixed $value, array &$params): ?array
    {
        $valueSql = static::valueSql($instances);
        return Db::parseNumericParam($valueSql, $value, columnType: static::dbType());
    }

    /**
     * @var int|float The minimum allowed number
     */
    public int|float $min = 0;

    /**
     * @var int|float The maximum allowed number
     */
    public int|float $max = 100;

    /**
     * @var int|float The step value for the input
     */
    public int|float $step = 1;

    /**
     * @var int|float|null The default value for new elements
     */
    public int|float|null $defaultValue = null;

    /**
     * @var string|null Text that should be displayed after the input
     */
    public ?string $suffix = null;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        unset($config['numberInputSize']);

        // Config normalization
        foreach (['min', 'max', 'step', 'defaultValue'] as $name) {
            if (isset($config[$name])) {
                $config[$name] = $this->_normalizeNumber($config[$name]);
            }
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['min', 'max', 'step', 'defaultValue'], 'number'];

        $rules[] = [
            ['max'],
            'compare',
            'compareAttribute' => 'min',
            'operator' => '>',
        ];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return $this->settingsHtml(false);
    }

    /**
     * @inheritdoc
     */
    public function getReadOnlySettingsHtml(): ?string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Range/settings.twig', [
            'field' => $this,
            'readOnly' => $readOnly,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function useFieldset(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value === null) {
            if (isset($this->defaultValue) && $this->isFresh($element)) {
                return $this->defaultValue;
            }
            return null;
        }

        return $this->_normalizeNumber($value);
    }

    /**
     * @param mixed $value
     * @return int|float|null
     */
    private function _normalizeNumber(mixed $value): int|float|null
    {
        // Was this submitted with a locale ID?
        if (isset($value['locale'], $value['value'])) {
            $value = Localization::normalizeNumber($value['value'], $value['locale']);
        }

        if ($value === '') {
            return null;
        }

        if (is_string($value) && is_numeric($value)) {
            if ((int)$value == $value) {
                return (int)$value;
            }
            if ((float)$value == $value) {
                return (float)$value;
            }
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Cp::rangeHtml([
            'id' => $this->getInputId(),
            'name' => $this->handle,
            'suffix' => $this->suffix,
            'step' => $this->step,
            'min' => $this->min,
            'max' => $this->max,
            'value' => $value,
            'labelId' => $this->getLabelId(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            ['number', 'min' => $this->min, 'max' => $this->max],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getElementConditionRuleType(): array|string|null
    {
        return NumberFieldConditionRule::class;
    }

    /**
     * @inheritdoc
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if ($value === null) {
            return '';
        }

        $formatted = Craft::$app->getFormatter()->asDecimal($value);

        if ($this->suffix) {
            $formatted = $formatted . $this->suffix;
        }

        return $formatted;
    }

    /**
     * @inheritdoc
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (!$value) {
            if ($this->step === 0) {
                // Zero is not really a valid HTML `step` attribute value, and we definitely can’t divide by it:
                $value = mt_rand($this->min, $this->max);
            } else {
                $value = mt_rand($this->min / $this->step, $this->max / $this->step) * $this->step;
            }
        }

        return $this->getPreviewHtml($value, $element ?? new Entry());
    }

    /**
     * @inheritdoc
     */
    public function getContentGqlType(): Type|array
    {
        return NumberType::getType();
    }

    /**
     * @inheritdoc
     */
    public function getContentGqlMutationArgumentType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => NumberType::getType(),
            'description' => $this->instructions,
        ];
    }
}
