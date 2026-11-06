<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Data\AssetTransformer;
use Illuminate\Container\Attributes\Singleton;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

/**
 * Registers asset transformer profiles in code rather than project config.
 *
 * A registered profile’s name, handle, and driver are fixed, and its settings are defaults. Saving the profile stores
 * settings overrides in project config, keyed by its UID; removing them restores the defaults. Registered profiles
 * can’t be deleted. Each registered profile’s UID is
 * derived from its handle, so it stays the same across environments and releases.
 *
 * Packages register profiles from their service provider:
 *
 * ```php
 * public function boot(AssetTransformerRegistry $transformers): void
 * {
 *     $transformers->register(new AssetTransformer([
 *         'name' => 'Remote Images',
 *         'handle' => 'remoteImages',
 *         'driver' => 'remote-images',
 *     ]));
 * }
 * ```
 *
 * @since 6.0.0
 */
#[Singleton]
class AssetTransformerRegistry
{
    public const string CRAFT_TRANSFORMER_UID = '0f7dbd20-f39e-57db-8b25-dcadccdd1733';

    /** @var array<string, AssetTransformer> */
    private array $transformers = [];

    public function __construct()
    {
        $this->transformers[self::CRAFT_TRANSFORMER_UID] = new AssetTransformer([
            'uid' => self::CRAFT_TRANSFORMER_UID,
            'name' => 'Craft',
            'handle' => 'craft',
            'driver' => 'craft',
            'settings' => [
                'disk' => null,
                'hasUrls' => false,
                'subpath' => null,
                'generateTransformsBeforePageLoad' => false,
            ],
        ]);
    }

    /**
     * @throws InvalidArgumentException if the profile is invalid, sets a UID, or conflicts with another registered profile.
     */
    public function register(AssetTransformer $transformer): void
    {
        if ($transformer->uid !== null) {
            throw new InvalidArgumentException("Asset transformer [{$transformer->handle}] can’t set a UID; registered transformers derive it from their handle.");
        }

        if (! $transformer->validate()) {
            throw new InvalidArgumentException("Asset transformer [{$transformer->handle}] is invalid: {$transformer->errors()->first()}");
        }

        if ($transformer->handle === 'craft') {
            throw new InvalidArgumentException('The reserved [craft] asset transformer cannot be registered.');
        }

        foreach ($this->transformers as $registered) {
            if ($registered->handle === $transformer->handle) {
                throw new InvalidArgumentException("An asset transformer with the handle [{$transformer->handle}] is already registered.");
            }
        }

        $transformer = clone $transformer;
        $transformer->uid = Uuid::uuid5(Uuid::NAMESPACE_URL, "craftcms:asset-transformer:{$transformer->handle}")->toString();
        $this->transformers[$transformer->uid] = $transformer;
    }

    public function has(?string $uid): bool
    {
        return $uid !== null && isset($this->transformers[$uid]);
    }

    public function get(string $uid): ?AssetTransformer
    {
        return isset($this->transformers[$uid]) ? clone $this->transformers[$uid] : null;
    }

    /**
     * The registered profiles with their default settings, keyed by UID.
     *
     * @return array<string, AssetTransformer>
     */
    public function all(): array
    {
        return array_map(fn (AssetTransformer $transformer): AssetTransformer => clone $transformer, $this->transformers);
    }
}
