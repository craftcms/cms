<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use craft\base\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Conditions\EmptyFieldConditionRule;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Gql\Types\DateTime as DateTimeType;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\Validation\Rules\TimeRule;
use DateTime;
use GraphQL\Type\Definition\Type;
use Override;
use yii\db\Schema;

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
        return sprintf('\\%s|null', DateTime::class);
    }

    #[Override]
    public static function dbType(): string
    {
        return Schema::TYPE_STRING;
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
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'minuteIncrement' => ['nullable', 'integer', 'min:1', 'max:60'],
            'min' => ['nullable', new TimeRule],
            'max' => ['nullable', new TimeRule(min: 'min')],
        ]);
    }

    public function getSettingsHtml(): string
    {
        return $this->settingsHtml(false);
    }

    #[Override]
    public function getReadOnlySettingsHtml(): string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        $incrementOptions = [5, 10, 15, 30, 60];
        $incrementOptions = array_combine($incrementOptions, $incrementOptions);

        return template('_components/fieldtypes/Time/settings', [
            'incrementOptions' => $incrementOptions,
            'field' => $this,
            'min' => $this->min ? DateTimeHelper::toDateTime(['time' => $this->min], true) : null,
            'max' => $this->max ? DateTimeHelper::toDateTime(['time' => $this->max], true) : null,
            'readOnly' => $readOnly,
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
            $value = new DateTime;
        }

        return $this->getPreviewHtml($value, $element ?? new Entry);
    }

    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): ?DateTime
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof DateTime) {
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
        /** @var DateTime|null $value */
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
