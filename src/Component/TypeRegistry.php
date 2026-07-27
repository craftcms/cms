<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component;

use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Base class for singleton catalogs of validated class-string types.
 *
 * Resolve a concrete catalog from the container and register extension types there:
 *
 * ```php
 * public function boot(FieldTypes $fieldTypes): void
 * {
 *     $fieldTypes->register(MyField::class);
 * }
 * ```
 *
 * @template T of object
 *
 * @internal
 */
abstract class TypeRegistry
{
    /** @var class-string<T>|null */
    protected const ?string CONTRACT = null;

    /** @var list<class-string<T>> */
    protected const array DEFAULT_TYPES = [];

    /** @var list<class-string<T>> */
    protected const array PROTECTED_TYPES = [];

    /** @var array<string, class-string<T>> */
    private array $types = [];

    public function __construct()
    {
        $this->register(...static::DEFAULT_TYPES);
    }

    /**
     * @param  class-string<T>  ...$types
     */
    public function register(string ...$types): void
    {
        $registeredTypes = $this->types;
        $reservedIdentities = $this->reservedIdentities();

        foreach ($types as $type) {
            $this->validate($type);

            $identity = $this->identity($type);
            $registeredType = $registeredTypes[$identity] ?? null;

            if ($registeredType !== null && $registeredType !== $type) {
                throw new InvalidArgumentException("Type [$type] shares identity [$identity] with [$registeredType].");
            }

            if (in_array($identity, $reservedIdentities, true)) {
                throw new InvalidArgumentException("Type [$type] uses reserved identity [$identity].");
            }

            $registeredTypes[$identity] = $type;
        }

        $this->types = $registeredTypes;
    }

    /**
     * @param  class-string  ...$types
     */
    public function remove(string ...$types): void
    {
        foreach ($types as $type) {
            $this->ensureRemovable($type);
        }

        foreach ($types as $type) {
            $identity = array_search($type, $this->types, true);

            if ($identity !== false) {
                unset($this->types[$identity]);
            }
        }
    }

    /**
     * @return Collection<int, class-string<T>>
     */
    public function types(): Collection
    {
        return new Collection(array_values($this->types));
    }

    /** @return Collection<string, class-string<T>> */
    protected function typesByIdentity(): Collection
    {
        return new Collection($this->types);
    }

    /** @param class-string<T> $type */
    protected function identity(string $type): string
    {
        return $type;
    }

    /** @return class-string<T>|null */
    protected function typeByIdentity(string $identity): ?string
    {
        return $this->types[$identity] ?? null;
    }

    /** @return list<string> */
    protected function reservedIdentities(): array
    {
        return [];
    }

    /** @param class-string<T> $type */
    private function validate(string $type): void
    {
        $contract = static::CONTRACT;

        if ($contract === null) {
            throw new InvalidArgumentException(sprintf('Registry [%s] must define a contract.', static::class));
        }

        if (! is_a($type, $contract, true)) {
            throw new InvalidArgumentException(sprintf('Type [%s] must implement or extend [%s].', $type, $contract));
        }
    }

    /** @param class-string $type */
    private function ensureRemovable(string $type): void
    {
        if (in_array($type, static::PROTECTED_TYPES, true)) {
            throw new InvalidArgumentException("Type [$type] cannot be removed.");
        }
    }
}
