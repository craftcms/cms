<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Concerns;

trait HasScenarios
{
    private string $_scenario = 'default';

    public function setScenario($scenario): void
    {
        $this->_scenario = $scenario;
    }

    public function getScenario(): string
    {
        return $this->_scenario;
    }

    public function scenarios(): array
    {
        return [];
    }

    public function inScenarios(string ...$scenarios): bool
    {
        return in_array($this->_scenario, $scenarios, true);
    }
}
