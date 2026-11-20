<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Field\BaseOptionsField;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Checkboxes;
use CraftCms\Cms\Field\Color;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Country;
use CraftCms\Cms\Field\Date;
use CraftCms\Cms\Field\Email;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Lightswitch;
use CraftCms\Cms\Field\Money;
use CraftCms\Cms\Field\MultiSelect;
use CraftCms\Cms\Field\Number;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\Range;
use CraftCms\Cms\Field\Time;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\Controls\ElementSelect;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiResolver;

/**
 * The input a destination column's default import value is entered with, matching what the column holds: a
 * field's own type and settings, or a property's or database column's type. Null means the column takes no
 * default, because no single value entered by hand suits it.
 *
 * Every default is stored as a string; a choice of several is a JSON list, which options fields decode, and so are
 * elements, by their IDs.
 *
 * @phpstan-type DefaultValueInput array{
 *     type: 'text'|'textarea'|'email'|'color'|'number'|'date'|'datetime'|'time'|'lightswitch'|'select'|'multiselect'|'elements',
 *     control?: array<string, mixed>,
 *     options?: list<array{value: string, label: string}>,
 *     placeholder?: string,
 *     maxLength?: int,
 *     min?: int|float|string,
 *     max?: int|float|string,
 *     step?: int|float,
 *     prefix?: string,
 *     suffix?: string,
 *     onLabel?: string,
 *     offLabel?: string,
 * }
 *
 * @since 6.0.0
 */
final class DefaultValueInputs
{
    /**
     * @return DefaultValueInput
     */
    public static function text(): array
    {
        return ['type' => 'text'];
    }

    /**
     * Elements, chosen with the given select control, which sets which ones can be chosen and how many.
     *
     * @return DefaultValueInput
     */
    public static function elements(ElementSelect $control): array
    {
        $payload = app(UiResolver::class)->resolve(Ui::make([UiField::make('', $control)]), new UiContext);

        return ['type' => 'elements', 'control' => self::firstControl($payload->nodes)?->jsonSerialize() ?? []];
    }

    /**
     * @param  list<NodePayload>  $nodes
     */
    private static function firstControl(array $nodes): ?ControlPayload
    {
        foreach ($nodes as $node) {
            if ($control = $node->control ?? self::firstControl($node->children ?? [])) {
                return $control;
            }
        }

        return null;
    }

    /**
     * @return DefaultValueInput|null
     */
    public static function forField(FieldInterface $field): ?array
    {
        $input = match (true) {
            $field instanceof PlainText => [
                'type' => $field->multiline ? 'textarea' : 'text',
                'placeholder' => $field->placeholder,
                'maxLength' => $field->charLimit,
            ],
            $field instanceof Email => ['type' => 'email', 'placeholder' => $field->placeholder],
            $field instanceof Color => ['type' => 'color'],
            $field instanceof Number => [
                'type' => 'number',
                'min' => $field->min,
                'max' => $field->max,
                'step' => $field->step ?? ($field->decimals > 0 ? 10 ** -$field->decimals : 1),
                'prefix' => $field->prefix,
                'suffix' => $field->suffix,
            ],
            $field instanceof Range => [
                'type' => 'number',
                'min' => $field->min,
                'max' => $field->max,
                'step' => $field->step,
                'suffix' => $field->suffix,
            ],
            // a whole number reads as the currency's minor units, so the amount is entered with its decimals
            $field instanceof Money => [
                'type' => 'number',
                'min' => $field->min,
                'max' => $field->max,
                'step' => 0.01,
                'prefix' => $field->currency,
            ],
            $field instanceof Date => [
                'type' => match (true) {
                    ! $field->showTime => 'date',
                    ! $field->showDate => 'time',
                    default => 'datetime',
                },
                'min' => $field->min?->format($field->showTime ? 'Y-m-d\TH:i' : 'Y-m-d'),
                'max' => $field->max?->format($field->showTime ? 'Y-m-d\TH:i' : 'Y-m-d'),
            ],
            $field instanceof Time => ['type' => 'time', 'min' => $field->min, 'max' => $field->max],
            $field instanceof Lightswitch => [
                'type' => 'lightswitch',
                'onLabel' => $field->onLabel,
                'offLabel' => $field->offLabel,
            ],
            $field instanceof Country => [
                'type' => 'select',
                'options' => self::options(app(Addresses::class)->getCountryList(app()->getLocale())),
            ],
            // the field's own select, so a default can be chosen from what the field can relate
            $field instanceof BaseRelationField && ($control = $field->uiControl(new FieldContext(['defaultValue']))) instanceof ElementSelect => self::elements($control),
            $field instanceof BaseOptionsField => [
                'type' => $field instanceof Checkboxes || $field instanceof MultiSelect ? 'multiselect' : 'select',
                'options' => array_values(array_map(
                    fn (array $option): array => ['value' => (string) $option['value'], 'label' => (string) $option['label']],
                    array_filter($field->options, fn (array $option): bool => ! isset($option['optgroup'])),
                )),
            ],
            default => null,
        };

        return $input === null ? null : self::withoutEmptySettings($input);
    }

    /**
     * For an importable property, by its declared types.
     *
     * @param  list<string>  $types
     * @return DefaultValueInput|null
     */
    public static function forPropertyTypes(array $types): ?array
    {
        return match (true) {
            array_any($types, fn (string $type) => is_a($type, \DateTimeInterface::class, true)) => ['type' => 'datetime'],
            in_array('bool', $types, true) => ['type' => 'lightswitch'],
            in_array('int', $types, true), in_array('float', $types, true) => ['type' => 'number'],
            in_array('string', $types, true) => self::text(),
            default => null,
        };
    }

    /**
     * For a database column, by its type name.
     *
     * @return DefaultValueInput
     */
    public static function forColumnType(string $typeName): array
    {
        return match (true) {
            in_array($typeName, ['bool', 'boolean'], true) => ['type' => 'lightswitch'],
            in_array($typeName, ['date'], true) => ['type' => 'date'],
            in_array($typeName, ['datetime', 'timestamp', 'timestamptz'], true) => ['type' => 'datetime'],
            in_array($typeName, ['time'], true) => ['type' => 'time'],
            (bool) preg_match('/^(tiny|small|medium|big)?int(eger)?\d*$|^(decimal|numeric|float\d*|double|real)$/', $typeName) => ['type' => 'number'],
            default => self::text(),
        };
    }

    /**
     * @param  array<string, string>  $labels  Labels by value.
     * @return list<array{value: string, label: string}>
     */
    private static function options(array $labels): array
    {
        return array_map(
            fn (string $value, string $label): array => ['value' => $value, 'label' => $label],
            array_keys($labels),
            array_values($labels),
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @return DefaultValueInput
     */
    private static function withoutEmptySettings(array $input): array
    {
        /** @var DefaultValueInput */
        return array_filter($input, fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
