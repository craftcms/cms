<?php

namespace craft\ui;

use Craft;
use craft\helpers\Cp;
use ReflectionClass;
use ReflectionMethod;

abstract class Component
{
    protected string $view;
    protected array $viewData = [];

    /**
     * @var array<string, array<string>>
     */
    protected array $methodCache = [];

    public function __construct()
    {
    }

    public function setup(): void
    {

    }

    public static function make(): static
    {
        $component = Craft::createObject(static::class);
        $component->setup();

        return $component;
    }

    public function withProps(array $properties): self
    {
        foreach ($properties as $key => $value) {
            $this->{$key}($value);
        }

        return $this;
    }

    public function viewData(array $data): self
    {
        $this->viewData = [
            ...$this->viewData,
            ...$data,
        ];

        return $this;
    }

    public function view(string $value): self
    {
        $this->view = $value;
        return $this;
    }

    public function getView(): string
    {
        return $this->view;
    }

    public function extractPublicMethods(): array
    {
        if (!isset($this->methodCache[$this::class])) {
            $reflection = new ReflectionClass($this);

            $this->methodCache[$this::class] = array_map(
                fn(ReflectionMethod $method): string => $method->getName(),
                $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            );
        }

        $values = [];

        foreach ($this->methodCache[$this::class] as $method) {
            if (str_starts_with($method, 'get')) {
                $key = lcfirst(substr($method, 3));
                $values[$key] = $this->$method();
            }
        }

        return $values;
    }

    public function render(): string
    {
        return Cp::renderTemplate($this->getView(),
            [
                'attributes' => [],
                ...$this->extractPublicMethods(),
                ...$this->viewData,
            ]
        );
    }

}