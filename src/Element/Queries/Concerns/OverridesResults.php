<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @template TValue of ElementInterface
 *
 * @internal
 */
trait OverridesResults
{
    /**
     * @var TValue[]|null The overridden element query result
     *
     * @see setResultOverride()
     */
    private ?array $override = null;

    /**
     * @var array|null The criteria params that were set when the cached element query result was set
     *
     * @see setResultOverride()
     */
    private ?array $overrideCriteria = null;

    /**
     * Returns the resulting elements set by [[setResultOverride()]], if the criteria params haven’t changed since then.
     *
     * @return TValue[]|null $elements The resulting elements, or null if setResultOverride() was never called or the criteria has changed
     *
     * @see setResultOverride()
     */
    public function getResultOverride(): ?array
    {
        if (! isset($this->override)) {
            return null;
        }

        // Make sure the criteria hasn't changed
        if ($this->overrideCriteria !== $this->getCriteria()) {
            return $this->override = null;
        }

        return $this->override;
    }

    /**
     * Sets the resulting elements.
     *
     * If this is called, [[all()]] will return these elements rather than initiating a new SQL query,
     * as long as none of the parameters have changed since setResultOverride() was called.
     *
     * @param  TValue[]  $elements  The resulting elements.
     *
     * @see getResultOverride()
     */
    public function setResultOverride(array $elements): void
    {
        $this->override = $elements;
        $this->overrideCriteria = $this->getCriteria();
    }

    /**
     * Clears the overridden result.
     *
     * @see getResultOverride()
     * @see setResultOverride()
     */
    public function clearResultOverride(): void
    {
        $this->override = $this->overrideCriteria = null;
    }
}
