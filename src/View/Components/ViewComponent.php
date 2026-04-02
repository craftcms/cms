<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Components;

use Closure;
use CraftCms\Cms\Support\Arr;
use Exception;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\View\ComponentAttributeBag;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class ViewComponent implements Htmlable
{
    protected string $view;

    protected array $viewData = [];

    public function viewData(array|Closure $data): static
    {
        $this->viewData[] = $data;

        return $this;
    }

    /**
     * @template T
     *
     * @param  T | callable(): T  $value
     * @return T
     */
    public function evaluate(mixed $value): mixed
    {
        if (! $value instanceof Closure) {
            return $value;
        }

        return $value();
    }

    protected function extractPublicMethods(): array
    {
        $reflection = new ReflectionClass($this);

        $methods = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methods[$method->getName()] = Closure::fromCallable([$this, $method->getName()]);
        }

        return $methods;
    }

    protected function extractPublicProperties(): array
    {
        $reflection = new ReflectionClass($this);

        $properties = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (! $property->isStatic()) {
                $properties[$property->getName()] = $property->getValue($this);
            }
        }

        return $properties;
    }

    public function getViewData(): array
    {
        return Arr::mapWithKeys(
            $this->viewData,
            fn (mixed $data): array => $this->evaluate($data) ?? [],
        );
    }

    /**
     * Set the view to be rendered.
     */
    public function view(?string $view, array|Closure $viewData = []): static
    {
        if ($view === null) {
            return $this;
        }

        $this->view = $view;

        if (filled($viewData)) {
            $this->viewData($viewData);
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraViewData(): array
    {
        return [];
    }

    public function getView(): string
    {
        if (isset($this->view)) {
            return $this->view;
        }

        throw new Exception('Class ['.static::class.'] extends ['.ViewComponent::class.'] but does not have a [$view] property defined.');
    }

    public function render(): View
    {
        return view($this->getView(), [
            ...$this->extractPublicMethods(),
            ...$this->extractPublicProperties(),
            'attributes' => new ComponentAttributeBag,
            ...$this->getExtraViewData(),
            ...$this->getViewData(),
        ]);
    }

    public function toHtml(): string
    {
        return $this->render()->render();
    }
}
