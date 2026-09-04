<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Conditions\NumberFieldConditionRule;
use CraftCms\Cms\Field\Contracts\DefaultableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Controls\Range as RangeControl;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Gql\Types\Number as NumberType;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Query;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Database\Query\Builder;
use Override;

use function CraftCms\Cms\t;

/**
 * Range represents a Range field, which provides a tactile UI around a numeric value.
 */
class Range extends Field implements DefaultableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface, SortableFieldInterface
{
    #[Override]
    public static function displayName(): string
    {
        return t('Range');
    }

    #[Override]
    public static function icon(): string
    {
        return 'slider';
    }

    #[Override]
    public function settingsForm(FormContext $context = new FormContext): Form
    {
        return Form::make([
            FormField::make(t('Min Value'), Number::make('min')->step('any')->value($this->min))->required(),
            FormField::make(t('Max Value'), Number::make('max')->step('any')->value($this->max))->required(),
            FormField::make(t('Step Size'), Number::make('step')->step('any')->value($this->step))->required(),
            FormField::make(t('Default Value'), Number::make('defaultValue')->step('any')->value($this->defaultValue)),
            FormField::make(t('Suffix Text'))
                ->instructions(t('Text that should be shown after the input.'))
                ->control(Text::make('suffix')->value($this->suffix)),
        ]);
    }

    #[Override]
    public function formControl(FieldContext $context): Control
    {
        return RangeControl::make($context->path)
            ->min($this->min)
            ->max($this->max)
            ->step($this->step)
            ->value($context->value);
    }

    #[Override]
    public static function phpType(): string
    {
        return 'int|null';
    }

    #[Override]
    public static function dbType(): string
    {
        return Query::TYPE_INTEGER;
    }

    #[Override]
    public static function modifyQuery(Builder $query, array $instances, mixed $value): void
    {
        $valueSql = self::valueSql($instances);

        $query->whereNumericParam($valueSql, $value, columnType: self::dbType());
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

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'min' => ['nullable', 'numeric'],
            'max' => ['nullable', 'numeric', 'gte:min'],
            'step' => ['nullable', 'numeric'],
            'defaultValue' => ['nullable', 'numeric'],
        ]);
    }

    #[Override]
    public function useFieldset(): bool
    {
        return true;
    }

    public function getDefaultValue(): int|float|null
    {
        return $this->defaultValue;
    }

    #[Override]
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
            $value = I18N::normalizeNumber($value['value'] ?? 0, $value['locale']);
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

    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return FormFields::rangeHtml([
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

    /** @return list<string> */
    #[Override]
    public function getElementRules(ElementInterface $element): array
    {
        return [
            'numeric',
            "min:$this->min",
            "max:$this->max",
        ];
    }

    public function getElementConditionRuleType(): string
    {
        return NumberFieldConditionRule::class;
    }

    #[Override]
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

    #[Override]
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

    #[Override]
    public function getContentGqlType(): Type
    {
        return NumberType::getType();
    }

    /** @return array{name: string, type: Type, description: string|null} */
    #[Override]
    public function getContentGqlMutationArgumentType(): array
    {
        return [
            'name' => $this->handle,
            'type' => NumberType::getType(),
            'description' => $this->instructions,
        ];
    }
}
