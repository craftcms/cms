<?php

namespace craft\services;

use craft\base\Component;
use craft\ui\components\StatusIndicator;

class Ui extends Component
{
    public function getComponents(): array
    {
        $components = [
            'status-indicator' => StatusIndicator::class,
        ];

        // TODO: Event to register custom components

        return $components;
    }

    public function resolveComponent(string $name)
    {
        $components = $this->getComponents();

        if (!isset($components[$name])) {
            throw new \Exception('Component not found: ' . $name);
        }

        $component = $components[$name];

        if (is_string($component)) {
            $component = $component::make();
        }

        return $component;
    }

    public function renderComponent(string $name, array $params = []): string
    {
        $component = $this->resolveComponent($name);

        foreach ($params as $key => $value) {
            $component->{$key}($value);
        }

        return $component->render();
    }
}
