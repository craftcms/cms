<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Form\Contracts\Control as ControlContract;
use CraftCms\Cms\Form\Enums\ChoicePresentation;
use CraftCms\Cms\Form\Enums\ControlMode;
use InvalidArgumentException;

/**
 * Builds Form controls from table column configuration.
 *
 * @since 6.0.0
 */
class TableColumn
{
    /** @param array<string, mixed> $config */
    public static function control(string $key, array $config): ControlContract
    {
        if (isset($config['control'])) {
            if (! $config['control'] instanceof ControlContract) {
                throw new InvalidArgumentException("Table column [{$key}] requires a Form Control.");
            }

            return $config['control']->withPath([$key]);
        }

        $type = $config['type'] ?? 'singleline';
        $control = match ($type) {
            'singleline', 'email', 'url' => Text::make([$key])->inputType($type === 'singleline' ? 'text' : $type),
            'heading' => Text::make([$key])->mode(ControlMode::ReadOnly),
            'multiline' => Textarea::make([$key])->rows($config['rows'] ?? 1)->monospace($config['code'] ?? false),
            'number' => Number::make([$key])->min($config['min'] ?? null)->max($config['max'] ?? null)->step($config['step'] ?? null),
            'date' => Date::make([$key]),
            'time' => Time::make([$key]),
            'color' => Color::make([$key]),
            'checkbox' => Checkbox::make([$key])->label($config['label'] ?? null),
            'lightswitch' => Lightswitch::make([$key])->size('small'),
            'select' => Choice::make([$key])->options(self::options($config['options'] ?? []))->withoutPlaceholder(),
            'radio' => Choice::make([$key])->options(self::options($config['options'] ?? []))->presentation(ChoicePresentation::Radios),
            'icon' => IconPicker::make([$key]),
            'autosuggest', 'template' => isset($config['textExpanderTriggers'])
                ? Text::make([$key])
                : Combobox::make([$key])->options($config['options'] ?? self::options($config['suggestions'] ?? []))->showAllOnEmpty(),
            'hidden' => Hidden::make([$key]),
            default => throw new InvalidArgumentException("Unknown Table column type [{$type}] at [{$key}]."),
        };

        if (($control instanceof Checkbox || $control instanceof Lightswitch) && isset($config['value'])) {
            $control->checkedValue($config['value']);
        }

        if ($control instanceof Text || $control instanceof Textarea || $control instanceof Combobox) {
            $control->placeholder($config['placeholder'] ?? null);
        }

        if (($control instanceof Text || $control instanceof Textarea) && isset($config['textExpanderTriggers'])) {
            $control->textExpanderTriggers($config['textExpanderTriggers']);
        }

        if ($control instanceof Text) {
            $control->monospace($config['code'] ?? false);
        }

        return $control;
    }

    /**
     * @param  array<array-key, mixed>  $options
     * @return list<array{label: string, value: string|int|float|bool}>
     */
    public static function options(array $options): array
    {
        $normalized = [];

        foreach ($options as $key => $option) {
            $normalized[] = is_array($option)
                ? ['label' => (string) ($option['label'] ?? $option['value'] ?? $key), 'value' => $option['value'] ?? $key]
                : ['label' => (string) $option, 'value' => $key];
        }

        return $normalized;
    }
}
