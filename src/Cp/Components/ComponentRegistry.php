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
        'button' => Button::class,
        'button-group' => ButtonGroup::class,
        'callout' => Callout::class,
        'checkbox' => Checkbox::class,
        'checkbox-group' => CheckboxGroup::class,
        'checkbox-select' => CheckboxSelect::class,
        'field' => Field::class,
        'field-group' => FieldGroup::class,
        'icon' => Icon::class,
        'input' => Input::class,
        'lightswitch' => Lightswitch::class,
        'radio' => Radio::class,
        'radio-group' => RadioGroup::class,
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
