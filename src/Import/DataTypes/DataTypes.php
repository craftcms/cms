<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\DataTypes;

use CraftCms\Cms\Component\TypeRegistry;
use Illuminate\Container\Attributes\Singleton;

/**
 * Registers the data types that import source files can be parsed as, by the file extensions they claim.
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
 * Several data types may claim the same extension. A file with that extension is read as the one whose
 * `matches()` accepts its contents, or, when more than one does, the one chosen for its import step.
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
     * Returns the file extensions claimed by the registered data types.
     *
     * @return list<string>
     */
    public function extensions(): array
    {
        return $this->types()
            ->flatMap(fn (string $type) => array_map(strtolower(...), $type::extensions()))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Returns the data types that claim a file extension.
     *
     * @param  string  $extension  The file extension, without a leading dot.
     * @return list<class-string<DataTypeInterface>>
     */
    public function forExtension(string $extension): array
    {
        $extension = strtolower($extension);

        return $this->types()
            ->filter(fn (string $type) => in_array($extension, array_map(strtolower(...), $type::extensions()), true))
            ->values()
            ->all();
    }

    /**
     * Returns the data types a file could be read as: the only one that claims its extension, or else those
     * whose `matches()` accepts the start of the file.
     *
     * @param  string  $extension  The file extension, without a leading dot.
     * @param  string  $sample  The first few kilobytes of the file.
     * @return list<class-string<DataTypeInterface>>
     */
    public function candidates(string $extension, string $sample): array
    {
        $types = $this->forExtension($extension);

        if (count($types) < 2) {
            return $types;
        }

        return array_values(array_filter($types, fn (string $type) => $type::matches($sample)));
    }
}
