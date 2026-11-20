<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Typecast;
use InvalidArgumentException;

/**
 * Asset transformer settings: the default transformer and the named transformers.
 *
 * ```php
 * ->assetTransformers(\CraftCms\Cms\Config\AssetTransformersConfig::create()
 *     ->default('imgix')
 *     ->transformer('craft', ['outputDisk' => 'transforms'])
 *     ->transformer('imgix', ['driver' => 'imgix', 'domain' => env('IMGIX_DOMAIN')])
 * )
 * ```
 *
 * @since 6.0.0
 */
class AssetTransformersConfig extends BaseConfig
{
    /**
     * The transformer for volumes that don’t choose one. `null` uses `craft`, and lets a hosting package such as Craft
     * Cloud’s set its own default without overriding a project that chose one.
     */
    public ?string $default = null;

    /**
     * Transformers keyed by name. Each has a `driver`, registered with `AssetTransformers::extend()`, and that driver’s
     * settings. The `craft` transformer is always defined, and its entry here only holds its settings.
     *
     * @var array<string, array<string, mixed>>
     */
    public array $transformers = [];

    /** @param array<string, mixed>|string $value */
    public static function fromConfig(array|string $value): self
    {
        $value = is_array($value) ? $value : Json::decode($value);

        if (! is_array($value)) {
            throw new InvalidArgumentException('The asset transformers configuration must be an array or a JSON object.');
        }

        Typecast::properties(self::class, $value);

        $config = self::create();

        foreach ($value as $property => $propertyValue) {
            if (property_exists($config, $property) && method_exists($config, $property)) {
                $config->{$property}($propertyValue);
            }
        }

        return $config;
    }

    public function default(?string $value): self
    {
        $this->default = $value !== '' ? $value : null;

        return $this;
    }

    /**
     * Defines a transformer. Its config needs a `driver`, and can hold that driver’s settings. Transformers with the
     * `craft` driver take `outputDisk` and `generateTransformsBeforePageLoad`. The `craft` transformer’s config can
     * leave out `driver`.
     *
     * @param  array<string, mixed>  $config
     *
     * @throws InvalidArgumentException if the name is empty, an integer, which PHP turns into an integer array key, or
     *                                  starts with `disk:`, which names disks’ deprecated `asset_transformer` keys, or
     *                                  if the `craft` transformer is given another driver.
     */
    public function transformer(string $name, array $config): self
    {
        // PHP turns integer-like array keys into integers, and `0` would resolve to the default transformer
        if ($name === '' || (string) (int) $name === $name) {
            throw new InvalidArgumentException('Asset transformer names can’t be empty or integers.');
        }

        if (str_starts_with($name, 'disk:')) {
            throw new InvalidArgumentException('Asset transformer names can’t start with “disk:”, which is reserved for disks’ deprecated `asset_transformer` keys.');
        }

        if ($name === 'craft' && ($config['driver'] ?? 'craft') !== 'craft') {
            throw new InvalidArgumentException('The craft transformer’s driver can’t be changed. Give the transformer another name.');
        }

        $this->transformers[$name] = $config;

        return $this;
    }

    /** @param array<string, array<string, mixed>> $value */
    public function transformers(array $value): self
    {
        $this->transformers = [];

        foreach ($value as $name => $config) {
            $this->transformer((string) $name, $config);
        }

        return $this;
    }

    /**
     * Returns a transformer’s config, or `null` if it isn’t defined. The `craft` transformer is always defined.
     *
     * @return array<string, mixed>|null
     */
    public function transformerConfig(string $name): ?array
    {
        if ($name === 'craft') {
            return ['driver' => 'craft', ...($this->transformers['craft'] ?? [])];
        }

        return $this->transformers[$name] ?? null;
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_values(array_unique(['craft', ...array_map(strval(...), array_keys($this->transformers))]));
    }
}
