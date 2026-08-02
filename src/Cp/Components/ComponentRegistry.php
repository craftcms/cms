<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Illuminate\Container\Attributes\Singleton;
use InvalidArgumentException;

/**
 * Maps template-facing component names to their {@see ViewComponent} classes,
 * backing the `ui()` helper and the `ui` Twig function. Plugins can register
 * additional components.
 */
#[Singleton]
class ComponentRegistry
{
    /** @var array<string, class-string<ViewComponent>> */
    private const array NATIVE_COMPONENTS = [
        'button' => Button::class,
        'button-group' => ButtonGroup::class,
        'callout' => Callout::class,
        'checkbox' => Checkbox::class,
        'checkbox-group' => CheckboxGroup::class,
        'checkbox-select' => CheckboxSelect::class,
        'combobox' => Combobox::class,
        'date-input' => DateInput::class,
        'editable-table' => EditableTable::class,
        'element-condition' => ElementCondition::class,
        'entry-type-select' => EntryTypeSelect::class,
        'field' => Field::class,
        'field-layout-designer' => FieldLayoutDesigner::class,
        'field-group' => FieldGroup::class,
        'group' => Group::class,
        'icon' => Icon::class,
        'input' => Input::class,
        'input-color' => InputColor::class,
        'input-password' => InputPassword::class,
        'lightswitch' => Lightswitch::class,
        'money-input' => MoneyInput::class,
        'number-input' => NumberInput::class,
        'radio' => Radio::class,
        'radio-group' => RadioGroup::class,
        'select' => Select::class,
        'tab' => Tab::class,
        'tabs' => Tabs::class,
        'text-input' => TextInput::class,
        'textarea' => Textarea::class,
        'time-input' => TimeInput::class,
    ];

    /** @var array<string, class-string<ViewComponent>> */
    private array $components;

    public function __construct()
    {
        $this->components = self::NATIVE_COMPONENTS;
    }

    /**
     * @internal
     *
     * @return array<string, class-string<ViewComponent>>
     */
    public function nativeComponents(): array
    {
        return self::NATIVE_COMPONENTS;
    }

    /**
     * @param  class-string<ViewComponent>  $class
     */
    public function register(string $name, string $class): void
    {
        if (! is_subclass_of($class, ViewComponent::class)) {
            throw new InvalidArgumentException(sprintf('%s is not a %s.', $class, ViewComponent::class));
        }

        $this->components[$name] = $class;
    }

    public function make(string $name, array $config = []): ViewComponent
    {
        $class = $this->components[$name] ?? throw new InvalidArgumentException(sprintf(
            'Unknown UI component "%s". Available: %s',
            $name,
            implode(', ', array_keys($this->components)),
        ));

        return $class::make()->configure($config);
    }
}
