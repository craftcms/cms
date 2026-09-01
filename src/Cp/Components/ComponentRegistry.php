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
    private array $components = [
        'action-menu' => ActionMenu::class,
        'button' => Button::class,
        'button-group' => ButtonGroup::class,
        'callout' => Callout::class,
        'checkbox' => Checkbox::class,
        'checkbox-group' => CheckboxGroup::class,
        'checkbox-select' => CheckboxSelect::class,
        'combobox' => Combobox::class,
        'field' => Field::class,
        'field-group' => FieldGroup::class,
        'icon' => Icon::class,
        'input' => Input::class,
        'input-color' => InputColor::class,
        'input-copy' => InputCopy::class,
        'input-date' => InputDate::class,
        'input-date-time' => InputDateTime::class,
        'input-handle' => InputHandle::class,
        'input-money' => InputMoney::class,
        'input-password' => InputPassword::class,
        'input-time' => InputTime::class,
        'lightswitch' => Lightswitch::class,
        'missing-component' => MissingComponent::class,
        'pane' => Pane::class,
        'permission-tree' => PermissionTree::class,
        'radio' => Radio::class,
        'radio-group' => RadioGroup::class,
        'select' => Select::class,
        'tab' => Tab::class,
        'tabs' => Tabs::class,
        'textarea' => Textarea::class,
    ];

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

    /** @param array<string, mixed> $config */
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
