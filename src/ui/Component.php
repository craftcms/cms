<?php

namespace craft\ui;

use Craft;
use craft\helpers\Cp;
use ReflectionClass;
use ReflectionMethod;

abstract class Component
{
    /**
     * @var string The view template path to render.
     */
    protected string $view;

    /**
     * @var array Raw view data to pass to the view.
     */
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

    /**
     * Create an instance of a component
     *
     * @return static
     * @throws \yii\base\InvalidConfigException
     */
    public static function make(): static
    {
        $component = Craft::createObject(static::class);
        $component->setup();

        return $component;
    }

    /**
     * Allows mass assignment of properties
     *
     * @param array $properties
     * @return $this
     */
    public function withProps(array $properties): static
    {
        foreach ($properties as $key => $value) {
            $this->{$key}($value);
        }

        return $this;
    }

    /**
     * Set view data for the component
     *
     * @param array $data
     * @return $this
     */
    public function viewData(array $data): static
    {
        $this->viewData = [
            ...$this->viewData,
            ...$data,
        ];

        return $this;
    }

    /**
     * Set the view template path
     *
     * @param string $value
     * @return $this
     */
    public function view(string $value): static
    {
        $this->view = $value;
        return $this;
    }

    /**
     * Get the view template path
     *
     * @return string
     */
    public function getView(): string
    {
        return $this->view;
    }

    /**
     * Transform all public `get` methods into data for the view.
     * Public methods beginning with `get` will be exposed to the view template
     * as a variable with the `get` prefix removed.
     *
     * @return array
     */
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

    /**
     * Get an array of HTML attributes for the parent element.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return [];
    }

    /**
     * Render the component
     *
     * @return string
     * @throws \craft\web\twig\TemplateLoaderException
     */
    public function render(): string
    {
        return Cp::renderTemplate($this->getView(),
            [
                'attributes' => new ComponentAttributeBag(array_filter($this->getAttributes())),
                ...$this->extractPublicMethods(),
                ...$this->viewData,
            ]
        );
    }
}
