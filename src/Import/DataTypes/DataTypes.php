<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\DataTypes;

use CraftCms\Cms\Component\TypeRegistry;
use Illuminate\Container\Attributes\Singleton;

/**
 * Registers the data types that import source files can be parsed as, keyed by file extension.
 *
 * Plugins may register data types from their service provider:
 *
 * ```php
 * public function boot(DataTypes $dataTypes): void
 * {
 *     $dataTypes->register(Yaml::class);
 * }
 * ```
 *
 * To replace a built-in data type, remove it before registering another for its extension.
 *
 * @extends TypeRegistry<DataTypeInterface>
 *
 * @since 6.0.0
 */
#[Singleton]
class DataTypes extends TypeRegistry
{
    protected const string CONTRACT = DataTypeInterface::class;

    protected const array DEFAULT_TYPES = [
        Json::class,
        Csv::class,
        Xml::class,
    ];

    /**
     * Returns the registered data types, keyed by file extension.
     *
     * @return array<string, class-string<DataTypeInterface>>
     */
    public function byExtension(): array
    {
        return $this->typesByIdentity()->all();
    }

    /**
     * Returns the data type registered for a file extension, if any.
     *
     * @param  string  $extension  The file extension, without a leading dot.
     * @return class-string<DataTypeInterface>|null
     */
    public function find(string $extension): ?string
    {
        return $this->typeByIdentity(strtolower($extension));
    }

    /** @param class-string<DataTypeInterface> $type */
    #[\Override]
    protected function identity(string $type): string
    {
        return strtolower($type::extension());
    }
}
