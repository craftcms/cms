<?php

declare(strict_types=1);

namespace CraftCms\Cms\Updates\Data;

/**
 * @internal
 */
readonly class Updates
{
    public function __construct(
        public Update $cms = new Update,
        /** @var Update[] */
        public array $plugins = [],
    ) {}

    public function getTotal(): int
    {
        return collect([$this->cms])
            ->push(...$this->plugins)
            ->sum(fn (Update $update) => $update->hasReleases() ? 1 : 0);
    }

    /**
     * Returns whether any of the updates have a critical release available.
     */
    public function hasCritical(): bool
    {
        return collect([$this->cms])
            ->push(...$this->plugins)
            ->contains(fn (Update $update) => $update->hasCritical());
    }

    public static function fromArray(array $data): self
    {
        return new self(
            cms: Update::fromArray($data['cms'] ?? []),
            plugins: array_map(Update::fromArray(...), $data['plugins'] ?? []),
        );
    }
}
