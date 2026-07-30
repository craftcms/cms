<?php

declare(strict_types=1);

namespace CraftCms\Cms\User;

use Closure;
use CraftCms\Cms\User\Data\PermissionGroup;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/** @internal */
#[Singleton]
class PermissionGroupCatalog
{
    /**
     * @var array<string, Closure(): (PermissionGroup|null)>
     */
    private array $factories = [];

    public function __construct(
        private readonly Container $container,
    ) {}

    /** @param Closure(): (PermissionGroup|null) $factory */
    public function register(string $handle, Closure $factory): void
    {
        $this->registerProvider($this->factories, $handle, $factory);
    }

    public function remove(string ...$handles): void
    {
        foreach ($handles as $handle) {
            unset($this->factories[$handle]);
        }
    }

    /**
     * @internal
     *
     * @param  Collection<int, PermissionGroup>  $groups
     * @return Collection<int, PermissionGroup>
     */
    public function apply(Collection $groups): Collection
    {
        foreach ($this->factories as $handle => $factory) {
            $group = $this->container->call($factory);

            if ($group === null) {
                continue;
            }

            if (! $group instanceof PermissionGroup) {
                throw new InvalidArgumentException("Permission group factory [$handle] must return a permission group.");
            }

            if ($group->handle !== $handle) {
                throw new InvalidArgumentException("Permission group factory [$handle] returned group handle [$group->handle].");
            }

            $groups->push($group);
        }

        $duplicateHandle = $groups->pluck('handle')->duplicates()->first();

        if ($duplicateHandle !== null) {
            throw new InvalidArgumentException("Permission group handle [$duplicateHandle] is duplicated.");
        }

        return $groups->values();
    }

    /** @param array<string, Closure> $providers */
    private function registerProvider(array &$providers, string $handle, Closure $provider): void
    {
        if ($handle === '') {
            throw new InvalidArgumentException('Permission group handles cannot be empty.');
        }

        if (isset($this->factories[$handle])) {
            throw new InvalidArgumentException("Permission group [$handle] is already registered.");
        }

        $providers[$handle] = $provider;
    }
}
