<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use Closure;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Registers providers that resolve the native fields available to field layouts.
 *
 * Providers run in registration order and receive the field layout and fields
 * returned by earlier providers. Additional dependencies are container-resolved.
 *
 * ```php
 * public function boot(NativeFields $nativeFields): void
 * {
 *     $nativeFields->register('my-plugin', function (FieldLayout $fieldLayout, array $fields): array {
 *         if ($fieldLayout->type === Entry::class) {
 *             $fields[] = MyEntryField::class;
 *         }
 *
 *         return $fields;
 *     });
 * }
 * ```
 */
#[Singleton]
class NativeFields
{
    /** @var array<string, Closure> */
    private array $providers = [];

    public function __construct(
        private readonly Container $container,
    ) {}

    public function register(string $handle, Closure $provider): void
    {
        if ($handle === '') {
            throw new InvalidArgumentException('Native field provider handles cannot be empty.');
        }

        if (isset($this->providers[$handle])) {
            throw new InvalidArgumentException("Native field provider [$handle] is already registered.");
        }

        $this->providers[$handle] = $provider;
    }

    public function remove(string ...$handles): void
    {
        foreach ($handles as $handle) {
            unset($this->providers[$handle]);
        }
    }

    public function apply(FieldLayout $fieldLayout, array $fields = []): array
    {
        foreach ($this->providers as $handle => $provider) {
            $fields = $this->container->call($provider, [
                'fieldLayout' => $fieldLayout,
                'fields' => $fields,
            ]);

            if (! is_array($fields)) {
                throw new InvalidArgumentException("Native field provider [$handle] must return an array.");
            }
        }

        return $fields;
    }
}
