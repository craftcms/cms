<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Typecast;
use InvalidArgumentException;

/**
 * Asset transformer settings: the default transformer, the `craft` transformer’s settings, and other named
 * transformers.
 *
 * ```php
 * ->assetTransformers(\CraftCms\Cms\Config\AssetTransformersConfig::create()
 *     ->default('imgix')
 *     ->outputDisk('transforms')
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
     * The disk the `craft` transformer stores transforms on, at its root. `null` stores them on each volume’s own disk.
     * Use a `scoped` disk to store them below a path.
     */
    public ?string $outputDisk = null;

    /** Whether the `craft` transformer generates each transform before returning its URL, instead of on its first request. */
    public bool $generateTransformsBeforePageLoad = false;

    /**
     * Transformers other than `craft`, keyed by name. Each has a `driver`, registered with `AssetTransformers::extend()`,
     * and that driver’s settings.
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

    public function outputDisk(?string $value): self
    {
        $this->outputDisk = $value !== '' ? $value : null;

        return $this;
    }

    public function generateTransformsBeforePageLoad(bool $value = true): self
    {
        $this->generateTransformsBeforePageLoad = $value;

        return $this;
    }

    /**
     * Defines a transformer. Its config needs a `driver`, and can hold that driver’s settings. A second transformer with
     * the `craft` driver takes `outputDisk` and `generateTransformsBeforePageLoad`.
     *
     * @param  array<string, mixed>  $config
     *
     * @throws InvalidArgumentException if the name is empty or `0`, which would resolve to the default transformer, or
     *                                  `craft`, whose settings have their own methods.
     */
    public function transformer(string $name, array $config): self
    {
        if ($name === '' || $name === '0') {
            throw new InvalidArgumentException('Asset transformer names can’t be empty or “0”.');
        }

        if ($name === 'craft') {
            throw new InvalidArgumentException('Set the craft transformer with outputDisk() and generateTransformsBeforePageLoad().');
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
     * Returns a transformer’s config, with the `craft` transformer built from its settings, or `null` if it isn’t
     * defined. Settings use the same camelCase names as this class’s methods, such as `outputDisk`.
     *
     * @return array<string, mixed>|null
     */
    public function transformerConfig(string $name): ?array
    {
        if ($name === 'craft') {
            return [
                'driver' => 'craft',
                'outputDisk' => $this->outputDisk,
                'generateTransformsBeforePageLoad' => $this->generateTransformsBeforePageLoad,
            ];
        }

        return $this->transformers[$name] ?? null;
    }

    /** @return list<string> */
    public function names(): array
    {
        return ['craft', ...array_map(strval(...), array_keys($this->transformers))];
    }
}
