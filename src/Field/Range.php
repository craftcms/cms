<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\fields\conditions\NumberFieldConditionRule;
use craft\gql\types\Number as NumberType;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Localization;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Support\Facades\I18N;
use GraphQL\Type\Definition\Type;
use yii\db\Schema;

use function CraftCms\Cms\t;

/**
 * Range represents a Range field, which provides a tactile UI around a numeric value.
 */
final class Range extends Field implements InlineEditableFieldInterface, MergeableFieldInterface, SortableFieldInterface
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function displayName(): string
    {
        return t('Range');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function icon(): string
    {
        return 'slider';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function phpType(): string
    {
        return 'int|null';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function dbType(): string
    {
        return Schema::TYPE_INTEGER;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function queryCondition(array $instances, mixed $value, array &$params): ?array
    {
        $valueSql = self::valueSql($instances);

        return Db::parseNumericParam($valueSql, $value, columnType: self::dbType());
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
     * {@inheritdoc}
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

    #[\Override]
    public static function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'min' => ['nullable', 'numeric'],
            'max' => ['nullable', 'numeric', 'gte:min'],
            'step' => ['nullable', 'numeric'],
            'defaultValue' => ['nullable', 'numeric'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsHtml(): string
    {
        return $this->settingsHtml(false);
    }

    /**
     * {@inheritdoc}
     */
    public function getReadOnlySettingsHtml(): string
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
     * {@inheritdoc}
     */
    #[\Override]
    public function useFieldset(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): int|null|float
    {
        if ($value === null) {
            if (isset($this->defaultValue) && $this->isFresh($element)) {
                return $this->defaultValue;
            }

            return null;
        }

        return $this->_normalizeNumber($value);
    }

    private function _normalizeNumber(mixed $value): int|float|null
    {
        // Was this submitted with a locale ID?
        if (isset($value['locale'])) {
            $value = Localization::normalizeNumber($value['value'] ?? 0, $value['locale']);
        }

        if ($value === '') {
            return null;
        }

        if (is_string($value) && is_numeric($value)) {
            if ((int) $value == $value) {
                return (int) $value;
            }
            if ((float) $value == $value) {
                return (float) $value;
            }
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
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
     * {@inheritdoc}
     */
    #[\Override]
    public function getElementValidationRules(): array
    {
        return [
            ['number', 'min' => $this->min, 'max' => $this->max],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getElementConditionRuleType(): string
    {
        return NumberFieldConditionRule::class;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if ($value === null) {
            return '';
        }

        $formatted = I18N::getFormatter()->asDecimal($value);

        if ($this->suffix) {
            return $formatted.$this->suffix;
        }

        return $formatted;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $value) {
            if ($this->step === 0) {
                // Zero is not really a valid HTML `step` attribute value, and we definitely can’t divide by it:
                $value = random_int($this->min, $this->max);
            } else {
                $value = random_int($this->min / $this->step, $this->max / $this->step) * $this->step;
            }
        }

        return $this->getPreviewHtml($value, $element ?? new Entry);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getContentGqlType(): Type
    {
        return NumberType::getType();
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getContentGqlMutationArgumentType(): array
    {
        return [
            'name' => $this->handle,
            'type' => NumberType::getType(),
            'description' => $this->instructions,
        ];
    }
}
