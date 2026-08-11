<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Conditions\EmptyFieldConditionRule;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Time as TimeControl;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Gql\Types\DateTime as DateTimeType;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\Validation\Rules\TimeRule;
use DateTimeInterface;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Date;
use Override;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * Date represents a Time field.
 */
class Time extends Field implements CrossSiteCopyableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface, SortableFieldInterface
{
    #[Override]
    public static function displayName(): string
    {
        return t('Time');
    }

    #[Override]
    public static function icon(): string
    {
        return 'clock';
    }

    #[Override]
    public static function phpType(): string
    {
        return sprintf('\\%s|null', DateTimeInterface::class);
    }

    #[Override]
    public static function dbType(): string
    {
        return Query::TYPE_STRING;
    }

    /**
     * @var string|null The minimum allowed time
     */
    public ?string $min = null;

    /**
     * @var string|null The maximum allowed time
     */
    public ?string $max = null;

    /**
     * @var int The number of minutes that the timepicker options should increment by
     */
    public int $minuteIncrement = 30;

    public function __construct($config = [])
    {
        // Config normalization
        foreach (['min', 'max'] as $name) {
            if (isset($config[$name]) && is_array($config[$name])) {
                if ($date = DateTimeHelper::toDateTime($config[$name], true)) {
                    $config[$name] = $date->format('H:i');
                } else {
                    unset($config[$name]);
                }
            }
        }
        parent::__construct($config);
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'min' => t('Min Time'),
            'max' => t('Max Time'),
        ];
    }

    #[Override]
    public function settingsForm(FormContext $context = new FormContext): Form
    {
        $increments = array_map(fn (int $increment): array => [
            'label' => (string) $increment,
            'value' => $increment,
        ], [5, 10, 15, 30, 60]);

        return Form::make([
            FormField::make(t('Minute Increment'))
                ->instructions(t('The number of minutes that timepicker options should be incremented by. (Authors can enter a specific time manually.)'))
                ->control(Choice::make('minuteIncrement')->options($increments)->value($this->minuteIncrement)),
            FormField::make(t('Min Time'), TimeControl::make('min')->value($this->min)),
            FormField::make(t('Max Time'), TimeControl::make('max')->value($this->max)),
        ]);
    }

    #[Override]
    public function formControl(FieldContext $context): Control
    {
        $value = $context->value instanceof DateTimeInterface
            ? $context->value->format('H:i')
            : $context->value;

        return TimeControl::make($context->path)
            ->min($this->min)
            ->max($this->max)
            ->step($this->minuteIncrement * 60)
            ->value($value);
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'minuteIncrement' => ['nullable', 'integer', 'min:1', 'max:60'],
            'min' => ['nullable', new TimeRule],
        ]);
    }

    #[Override]
    public function getInputId(): string
    {
        return sprintf('%s-time', parent::getInputId());
    }

    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return template('_includes/forms/time', [
            'id' => parent::getInputId(), // can't use $this->getInputId() here because the template adds the "-time"
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'value' => $value,
            'minTime' => DateTimeHelper::timeToSeconds($this->min),
            'maxTime' => DateTimeHelper::timeToSeconds($this->max),
            'minuteIncrement' => $this->minuteIncrement,
        ]);
    }

    /** @return list<TimeRule> */
    #[Override]
    public function getElementRules(ElementInterface $element): array
    {
        return [
            new TimeRule($this->min, $this->max),
        ];
    }

    #[Override]
    protected function searchKeywords(mixed $value, ElementInterface $element): string
    {
        return '';
    }

    #[Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if (! $value) {
            return '';
        }

        return I18N::getFormatter()->asTime($value, Locale::LENGTH_SHORT);
    }

    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $value) {
            $value = now();
        }

        return $this->getPreviewHtml($value, $element ?? new Entry);
    }

    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): ?DateTimeInterface
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        if (is_array($value)) {
            if (empty($value['time'])) {
                return null;
            }

            return DateTimeHelper::toDateTime($value, true) ?: null;
        }

        return DateTimeHelper::toDateTime(['time' => $value], true) ?: null;
    }

    #[Override]
    public function serializeValue(mixed $value, ?ElementInterface $element): ?string
    {
        /** @var DateTimeInterface|null $value */
        return $value?->format('H:i:s');
    }

    #[Override]
    public function serializeValueForDb(mixed $value, ?ElementInterface $element): mixed
    {
        // Bypass Db::prepareDateForDb()
        return $this->serializeValue($value, $element);
    }

    public function getElementConditionRuleType(): string
    {
        return EmptyFieldConditionRule::class;
    }

    #[Override]
    public function getContentGqlType(): Type
    {
        return DateTimeType::getType();
    }

    /** @return array{name: string, type: Type, description: string|null} */
    #[Override]
    public function getContentGqlMutationArgumentType(): array
    {
        return [
            'name' => $this->handle,
            'type' => DateTimeType::getType(),
            'description' => $this->instructions,
        ];
    }
}
