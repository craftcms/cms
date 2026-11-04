<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\CommandPalette;

use Closure;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Providers that add command palette items for a user, keyed by handle.
 *
 * Each provider is called with the `$user` the palette is listing items for,
 * plus any other dependencies it type-hints, and returns the items to add.
 * Only return items the user can use.
 *
 * @see PageRegistry
 * @see ActionRegistry
 * @since 6.0.0
 */
abstract class ItemRegistry
{
    /** @var array<string, Closure(CraftUser): iterable<CommandPaletteItem>> */
    private array $providers = [];

    public function __construct(
        private readonly Container $container,
    ) {}

    /** @param Closure(CraftUser): iterable<CommandPaletteItem> $provider */
    public function register(string $handle, Closure $provider): void
    {
        if ($handle === '') {
            throw new InvalidArgumentException('Command palette item provider handles cannot be empty.');
        }

        if (isset($this->providers[$handle])) {
            throw new InvalidArgumentException("Command palette item provider [$handle] is already registered.");
        }

        $this->providers[$handle] = $provider;
    }

    public function remove(string ...$handles): void
    {
        foreach ($handles as $handle) {
            unset($this->providers[$handle]);
        }
    }

    /**
     * The registered providers' items for the user, in registration order.
     *
     * @return list<CommandPaletteItem>
     */
    public function items(CraftUser $user): array
    {
        $items = [];

        foreach ($this->providers as $handle => $provider) {
            foreach ($this->container->call($provider, ['user' => $user]) as $item) {
                if (! $item instanceof CommandPaletteItem) {
                    throw new InvalidArgumentException(sprintf('Command palette item provider [%s] must return %s instances.', $handle, CommandPaletteItem::class));
                }

                $items[] = $item;
            }
        }

        return $items;
    }
}
