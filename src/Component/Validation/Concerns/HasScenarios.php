<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Validation\Concerns;

trait HasScenarios
{
    private string $_scenario = 'default';

    /**
     * {@inheritDoc}
     */
    public function setScenario($scenario): void
    {
        $this->_scenario = $scenario;
    }

    /**
     * {@inheritDoc}
     */
    public function getScenario(): string
    {
        return $this->_scenario;
    }

    /**
     * {@inheritDoc}
     */
    public function scenarios(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    final public function inScenarios(string ...$scenarios): bool
    {
        return in_array($this->_scenario, $scenarios, true);
    }
}
