<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\ImportableElementContainerFieldInterface;
use CraftCms\Cms\FieldLayout\Contracts\ImportableFieldLayoutElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Support\Attributes\Importable;
use CraftCms\Cms\Support\Facades\Fields;

class ImportHelper
{
    public static function getImportableProperties(BaseImporter $importer): array
    {
        // automatically include all Importable properties (e.g. sectionId, typeId for Entry);
        $class = new \ReflectionClass($importer->className);
        $properties = $class->getProperties();
        $properties = array_values(array_filter($properties, fn ($property) => ! empty($property->getAttributes(Importable::class))));

        return array_map(function ($property) {
            $attribute = $property->getAttributes(Importable::class)[0];
            $arguments = $attribute->getArguments();

            return [
                'property' => $property->getName(),
                'name' => $arguments[0] ?? $arguments['name'],
                'label' => $arguments[1] ?? $arguments[0] ?? $arguments['label'],
                'excludeFromUiMapping' => $arguments[2] ?? $arguments['excludeFromUiMapping'] ?? false,
                'isContainer' => $arguments[3] ?? $arguments['isContainer'] ?? false,
                'canBeMatchCriteria' => $arguments[4] ?? $arguments['canBeMatchCriteria'] ?? true,
                'canBeCleared' => $arguments[5] ?? $arguments['canBeCleared'] ?? true,
                'defaultValue' => $property->getDefaultValue(),
            ];
        }, $properties);
    }

    public static function getImportableContainerProperties(BaseImporter $importer): array
    {
        $importableProperties = self::getImportableProperties($importer);
        $importableContainerProperties = [];

        foreach ($importableProperties as $property) {
            if ($property['isContainer']) {
                $importableContainerProperties[] = $property;
            }
        }

        return $importableContainerProperties;
    }

    public static function getDestinationColsForFieldLayout(
        ?FieldLayout $fieldLayout,
        ?FieldInterface $ownerField = null,
        mixed $provider = null,
        ?string $prefix = null
    ): array {
        $cols = [];
        if ($fieldLayout) {
            $allElements = $fieldLayout->getAllElements();

            foreach ($allElements as $fieldLayoutElement) {
                if ($fieldLayoutElement instanceof ImportableFieldLayoutElementInterface) {
                    // get element's fields for mapping; for example,
                    // lat/long has two fields;
                    // addresses field has (by default) label, country code and address field which then contains a bunch of other fields;
                    // and custom fields have yet another way of getting this
                    $cols[] = $fieldLayoutElement->getFieldsForMapping($fieldLayout, $ownerField, $provider, $prefix);
                }
            }
        }

        return $cols;
    }

    public static function getDestinationColsForProperty(
        BaseImporter $importer,
        string $property,
        ?FieldLayout $fieldLayout,
        ?string $prefix = null
    ): array {
        $cols = [];
        $fieldLayout = Fields::getLayoutByType(Address::class);
        if ($fieldLayout) {
            $allElements = $fieldLayout->getAllElements();

            foreach ($allElements as $fieldLayoutElement) {
                if ($fieldLayoutElement instanceof ImportableFieldLayoutElementInterface) {
                    // get element's fields for mapping; for example,
                    // lat/long has two fields;
                    // addresses field has (by default) label, country code and address field which then contains a bunch of other fields;
                    // and custom fields have yet another way of getting this
                    $cols[] = $fieldLayoutElement->getFieldsForMapping($fieldLayout, null, null, $prefix);
                }
            }
        }

        return $cols;
    }

    /**
     * Ensures that if the initial value is an array, any json encoded arrays in it are decoded.
     */
    public static function ensureCleanArray(mixed $value): mixed
    {
        if (! empty($value) && is_string($value) &&
            $decoded = json_decode($value, true)) {
            return $decoded;
        }
        if (is_array($value)) {
            return array_map(self::ensureCleanArray(...), $value);
        }

        return $value;
    }

    public static function getPrefixedHandlesForMapping(
        string $attribute,
        ?FieldInterface $ownerField,
        mixed $field,
        ?FieldLayout $fieldLayout,
        mixed $provider,
        ?string $prefix = null,
    ): array {
        if ($ownerField instanceof ImportableElementContainerFieldInterface) {
            $namePrefix = $ownerField->getMappingUiPrefix($fieldLayout, $provider, $prefix);
            if ($field instanceof FieldInterface) {
                $prefixedHandle = $namePrefix."[fields][$attribute]";
            } else {
                $prefixedHandle = $namePrefix."[$attribute]";
            }
        } else {
            $prefixedHandle = ! empty($prefix) ? $prefix."[$attribute]" : $attribute;
        }

        $prefixedHandleForMap = Html::namespaceInputName($prefixedHandle, 'map');
        $prefixedHandleForMatchCriteria = Html::namespaceInputName($prefixedHandle, 'matchCriteria');
        $prefixedHandleForClear = Html::namespaceInputName($prefixedHandle, 'clearableItems');
        $prefixedHandleAsArray = Arr::bracketsToArray($prefixedHandle);

        return [$prefixedHandleForMap, $prefixedHandleForMatchCriteria, $prefixedHandleForClear, $prefixedHandle, $prefixedHandleAsArray];
    }

    public static function remapData(array $map, array $data): array
    {
        return self::mapNode($map, $data, $data, null, true)['data'];
    }

    protected static function mapNode(
        array $map,
        array $rootData,
        mixed $currentData,
        ?string $currentBasePath,
        bool $keepUnused
    ): array {
        $result = [];
        $consumedKeys = [];

        foreach ($map as $targetKey => $rule) {
            if (is_array($rule)) {
                $source = self::findSourceForRule((string) $targetKey, $rule, $rootData, $currentData, $currentBasePath);

                if ($source === null) {
                    $nested = self::mapNode($rule, $rootData, $currentData, $currentBasePath, false);
                    $result[$targetKey] = $nested['data'];
                    array_push($consumedKeys, ...$nested['consumed']);

                    continue;
                }

                array_push($consumedKeys, ...$source['consumed']);

                if (self::blockLooksGroupedByType($rule, $source['value'])) {
                    $mapped = self::mapGroupedBlocksByType($rule, $rootData, $source['value'], $source['basePath']);
                    $result[$targetKey] = $mapped['data'];
                    array_push($consumedKeys, ...$mapped['consumed']);
                } elseif (array_is_list($source['value'])) {
                    $result[$targetKey] = array_map(
                        fn ($row) => is_array($row)
                            ? self::mapRowByOwnType($rule, $rootData, $row, $source['basePath'])
                            : $row,
                        $source['value']
                    );
                } else {
                    $result[$targetKey] = self::mapNode($rule, $rootData, $source['value'], $source['basePath'], true)['data'];
                }

                continue;
            }

            if ($rule === null) {
                // $result[$targetKey] = null;

                continue;
            }

            if ($rule === '""') {
                $result[$targetKey] = '';

                continue;
            }

            $mapped = self::mapScalarValue((string) $rule, $rootData, $currentData, $currentBasePath);
            $result[$targetKey] = $mapped['value'];

            if ($mapped['consumed'] !== null) {
                $consumedKeys[] = $mapped['consumed'];
            }
        }

        if ($keepUnused && is_array($currentData)) {
            foreach ($currentData as $key => $value) {
                if (! in_array($key, $consumedKeys, true) && ! array_key_exists($key, $result)) {
                    $result[$key] = $value;
                }
            }
        }

        return [
            'data' => $result,
            'consumed' => array_values(array_unique($consumedKeys)),
        ];
    }

    protected static function findSourceForRule(
        string $targetKey,
        array $rule,
        array $rootData,
        mixed $currentData,
        ?string $currentBasePath
    ): ?array {
        if (is_array($currentData) && array_key_exists($targetKey, $currentData) && is_array($currentData[$targetKey])) {
            return [
                'value' => $currentData[$targetKey],
                'basePath' => self::pathJoin($currentBasePath, $targetKey),
                'consumed' => [$targetKey],
            ];
        }

        $sourceRelativePath = self::sharedSourcePath($rule, $currentBasePath);

        if ($sourceRelativePath === null || $sourceRelativePath === '') {
            return null;
        }

        $sourceKey = explode('.', $sourceRelativePath)[0];

        // If any direct scalar rule points exactly to the resolved source path, that scalar is
        // consuming the source container as its whole value — not iterating over its items.
        // Return null so mapNode processes this rule as a flat dict instead.
        $sourceAbsPath = self::pathJoin($currentBasePath, $sourceRelativePath);
        foreach ($rule as $ruleValue) {
            if (is_string($ruleValue) && $ruleValue === $sourceAbsPath) {
                return null;
            }
        }

        if (is_array($currentData) && $source = self::getArrayAtPath($currentData, $sourceRelativePath, self::pathJoin($currentBasePath, $sourceRelativePath), $sourceKey)) {
            return $source;
        }

        $absolutePath = self::pathJoin($currentBasePath, $sourceRelativePath);

        if ($source = self::getArrayAtPath($rootData, $absolutePath, $absolutePath, $sourceKey)) {
            return $source;
        }

        return null;
    }

    /**
     * Resolves a dotted `$path` inside `$data` to an array value, or `null` if the path is
     * missing or doesn't resolve to an array.
     */
    protected static function getArrayAtPath(array $data, string $path, string $basePath, string $sourceKey): ?array
    {
        $found = self::findPath($data, $path);

        if (! $found['found'] || ! is_array($found['value'])) {
            return null;
        }

        return [
            'value' => $found['value'],
            'basePath' => $basePath,
            'consumed' => [$sourceKey],
        ];
    }

    /**
     * Maps a single row of an inline-typed (flat, `type`-per-row) block list, dispatching
     * to only the rule's submap matching the row's own `type` when the rule declares one.
     */
    protected static function mapRowByOwnType(array $rule, array $rootData, array $row, ?string $basePath): array
    {
        if (array_key_exists('type', $row) && is_string($row['type']) && array_key_exists($row['type'], $rule) && is_array($rule[$row['type']])) {
            return self::mapRowForType($row['type'], $rule[$row['type']], $rootData, $row, $basePath);
        }

        return self::mapNode($rule, $rootData, $row, $basePath, true)['data'];
    }

    protected static function mapGroupedBlocksByType(array $blockTypeMap, array $rootData, array $sourceValue, string $basePath): array
    {
        $items = [];
        $consumedKeys = [];

        foreach ($blockTypeMap as $type => $typeMap) {
            if (! isset($sourceValue[$type])) {
                continue;
            }
            if (! is_array($sourceValue[$type])) {
                continue;
            }
            if (! array_is_list($sourceValue[$type])) {
                continue;
            }
            $consumedKeys[] = $type;
            $typeBasePath = self::pathJoin($basePath, (string) $type);

            foreach ($sourceValue[$type] as $row) {
                if (is_array($row)) {
                    $items[] = self::mapRowForType((string) $type, $typeMap, $rootData, $row, $typeBasePath);
                }
            }
        }

        return [
            'data' => $items,
            'consumed' => array_values(array_unique($consumedKeys)),
        ];
    }

    /**
     * Maps `$row` against `$typeMap`, tagging the result with its block `$type`.
     */
    protected static function mapRowForType(string $type, array $typeMap, array $rootData, array $row, ?string $basePath): array
    {
        return ['type' => $type] + self::mapNode($typeMap, $rootData, $row, $basePath, true)['data'];
    }

    protected static function mapScalarValue(string $path, array $rootData, mixed $currentData, ?string $currentBasePath): array
    {
        if ($currentBasePath !== null && str_starts_with($path, $currentBasePath.'.')) {
            $relativePath = substr($path, strlen($currentBasePath) + 1);

            return [
                'value' => is_array($currentData) ? self::findPath($currentData, $relativePath)['value'] : null,
                'consumed' => explode('.', $relativePath)[0],
            ];
        }

        return [
            'value' => self::findPath($rootData, $path)['value'],
            'consumed' => $currentBasePath === null ? explode('.', $path)[0] : null,
        ];
    }

    protected static function sharedSourcePath(array $map, ?string $currentBasePath): ?string
    {
        $paths = self::collectLeafPaths($map, $currentBasePath);

        if ($paths === []) {
            return null;
        }

        $common = explode('.', (string) array_shift($paths));

        foreach ($paths as $path) {
            $parts = explode('.', (string) $path);
            $next = [];

            for ($i = 0, $max = min(count($common), count($parts)); $i < $max; $i++) {
                if ($common[$i] !== $parts[$i]) {
                    break;
                }

                $next[] = $common[$i];
            }

            $common = $next;
        }

        foreach ($common as $index => $part) {
            if (array_key_exists($part, $map)) {
                return $index === 0 ? null : implode('.', array_slice($common, 0, $index));
            }
        }

        return implode('.', $common);
    }

    protected static function collectLeafPaths(array $map, ?string $currentBasePath): array
    {
        $paths = [];

        foreach ($map as $rule) {
            if (is_array($rule)) {
                array_push($paths, ...self::collectLeafPaths($rule, $currentBasePath));
            } elseif (is_string($rule)) {
                if ($currentBasePath !== null && str_starts_with($rule, $currentBasePath.'.')) {
                    $paths[] = substr($rule, strlen($currentBasePath) + 1);
                } elseif ($currentBasePath === null) {
                    $paths[] = $rule;
                }
            }
        }

        return $paths;
    }

    protected static function blockLooksGroupedByType(array $rule, mixed $sourceValue): bool
    {
        if (! is_array($sourceValue) || array_is_list($sourceValue)) {
            return false;
        }

        // A genuine block-type map only ever declares type submaps at its top level; a mix of
        // scalar/null field rules alongside a submap means this is a block's own fields map
        // (one of which happens to be a nested container field), not a type-dispatch map.
        if (array_any($rule, fn ($childRule) => ! is_array($childRule))) {
            return false;
        }

        return array_any($rule, fn ($_, $childKey) => array_key_exists((string) $childKey, $sourceValue) && is_array($sourceValue[$childKey]) && array_is_list($sourceValue[$childKey]));
    }

    /**
     * Walks a dotted `$path` inside `$data`, returning whether it was found and its value.
     *
     * @return array{found: bool, value: mixed}
     */
    protected static function findPath(array $data, string $path): array
    {
        $current = $data;

        foreach (explode('.', $path) as $part) {
            if (! is_array($current) || ! array_key_exists($part, $current)) {
                return ['found' => false, 'value' => null];
            }

            $current = $current[$part];
        }

        return ['found' => true, 'value' => $current];
    }

    protected static function pathJoin(?string $basePath, string $key): string
    {
        return $basePath === null || $basePath === '' ? $key : $basePath.'.'.$key;
    }
}
