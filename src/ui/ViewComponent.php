<?php

namespace craft\ui;

use Closure;
use craft\helpers\Cp;
use Exception;
use Filament\Support\Components\Component as FilamentComponent;
use Illuminate\Contracts\Support\Htmlable;
use ReflectionClass;
use ReflectionMethod;

abstract class ViewComponent extends FilamentComponent implements Htmlable
{
    /**
     * @var string
     */
    protected string $view;

    /**
     * @var string | Closure | null
     */
    protected string|Closure|null $defaultView = null;

    /**
     * @var array<string, mixed>
     */
    protected array $viewData = [];

    protected string $viewIdentifier;

    /**
     * @var array<string, array<string>>
     */
    protected array $methodCache = [];

    /**
     * @param string | null $view
     * @param array<string, mixed> $viewData
     * @return static
     */
    public function view(?string $view, array $viewData = []): static
    {
        if ($view === null) {
            return $this;
        }

        $this->view = $view;

        if ($viewData !== []) {
            $this->viewData($viewData);
        }

        return $this;
    }

    /**
     * @param array<string, mixed> $data
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
     * @param string | Closure | null $view
     * @return static
     */
    public function defaultView(string|Closure|null $view): static
    {
        $this->defaultView = $view;

        return $this;
    }

    public function toHtml(): string
    {
        return $this->render();
    }

    public function render(): string
    {
        return Cp::renderTemplate($this->getView(), [
            'attributes' => new ComponentAttributeBag(),
            ...$this->extractPublicMethods(),
            ...(isset($this->viewIdentifier) ? [$this->viewIdentifier => $this] : []),
            ...$this->viewData,
        ]);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getView(): string
    {
        if (isset($this->view)) {
            return $this->view;
        }

        if (filled($defaultView = $this->getDefaultView())) {
            return $defaultView;
        }

        throw new Exception('Class [' . static::class . '] extends [' . ViewComponent::class . '] but does not have a [$view] property defined.');
    }

    /**
     * @return string | null
     */
    public function getDefaultView(): ?string
    {
        return $this->evaluate($this->defaultView);
    }

    /**
     * @return array<string, Closure>
     */
    protected function extractPublicMethods(): array
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
}
