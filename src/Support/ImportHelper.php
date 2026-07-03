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
    public static function getImportableProperties(BaseImporter $config): array
    {
        // automatically include all Importable properties (e.g. sectionId, typeId for Entry);
        $class = new \ReflectionClass($config->className);
        $properties = $class->getProperties();
        $properties = array_values(array_filter($properties, fn ($property) => ! empty($property->getAttributes(Importable::class))));

        return array_map(function ($property) {
            $attribute = $property->getAttributes(Importable::class)[0];
            $arguments = $attribute->getArguments();

            return [
                'property' => $property->getName(),
                'name' => $arguments[0],
                'label' => $arguments[1] ?? $arguments[0],
                'excludeFromUiMapping' => $arguments[2] ?? false,
                'isContainer' => $arguments[3] ?? false,
                'canBeMatchCriteria' => $arguments[4] ?? true,
                'defaultValue' => $property->getDefaultValue(),
            ];
        }, $properties);
    }

    public static function getImportableContainerProperties(BaseImporter $config): array
    {
        $importableProperties = self::getImportableProperties($config);
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
        BaseImporter $config,
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
        $prefixedHandleAsArray = Arr::bracketsToArray($prefixedHandle);

        return [$prefixedHandleForMap, $prefixedHandleForMatchCriteria, $prefixedHandle, $prefixedHandleAsArray];
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
                $source = self::resolveSourceForArrayRule((string) $targetKey, $rule, $rootData, $currentData, $currentBasePath);

                if ($source === null) {
                    $nested = self::mapNode($rule, $rootData, $currentData, $currentBasePath, false);
                    $result[$targetKey] = $nested['data'];
                    array_push($consumedKeys, ...$nested['consumed']);

                    continue;
                }

                array_push($consumedKeys, ...$source['consumed']);

                if (self::isBlockTypeContainer($rule, $source['value'])) {
                    $mapped = self::mapBlockTypeContainer($rule, $rootData, $source['value'], $source['basePath']);
                    $result[$targetKey] = $mapped['data'];
                    array_push($consumedKeys, ...$mapped['consumed']);
                } elseif (array_is_list($source['value'])) {
                    $result[$targetKey] = array_map(
                        fn ($row) => is_array($row)
                            ? self::mapNode($rule, $rootData, $row, $source['basePath'], true)['data']
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

    protected static function resolveSourceForArrayRule(
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

        $sourceRelativePath = self::commonRelativeSourcePath($rule, $currentBasePath);

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

        if (is_array($currentData) && self::pathExists($currentData, $sourceRelativePath)) {
            $value = self::getPath($currentData, $sourceRelativePath);

            if (is_array($value)) {
                return [
                    'value' => $value,
                    'basePath' => self::pathJoin($currentBasePath, $sourceRelativePath),
                    'consumed' => [$sourceKey],
                ];
            }
        }

        $absolutePath = self::pathJoin($currentBasePath, $sourceRelativePath);

        if (self::pathExists($rootData, $absolutePath)) {
            $value = self::getPath($rootData, $absolutePath);

            if (is_array($value)) {
                return [
                    'value' => $value,
                    'basePath' => $absolutePath,
                    'consumed' => [$sourceKey],
                ];
            }
        }

        return null;
    }

    protected static function mapBlockTypeContainer(array $blockTypeMap, array $rootData, array $sourceValue, string $basePath): array
    {
        $items = [];
        $consumedKeys = [];

        foreach ($blockTypeMap as $type => $typeMap) {
            if (! array_key_exists($type, $sourceValue)) {
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
                    $items[] = ['type' => $type] + self::mapNode($typeMap, $rootData, $row, $typeBasePath, true)['data'];
                }
            }
        }

        return [
            'data' => $items,
            'consumed' => array_values(array_unique($consumedKeys)),
        ];
    }

    protected static function mapScalarValue(string $path, array $rootData, mixed $currentData, ?string $currentBasePath): array
    {
        if ($currentBasePath !== null && str_starts_with($path, $currentBasePath.'.')) {
            $relativePath = substr($path, strlen($currentBasePath) + 1);

            return [
                'value' => is_array($currentData) ? self::getPath($currentData, $relativePath) : null,
                'consumed' => explode('.', $relativePath)[0],
            ];
        }

        return [
            'value' => self::getPath($rootData, $path),
            'consumed' => $currentBasePath === null ? explode('.', $path)[0] : null,
        ];
    }

    protected static function commonRelativeSourcePath(array $map, ?string $currentBasePath): ?string
    {
        $paths = self::collectRelativeLeafPaths($map, $currentBasePath);

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

    protected static function collectRelativeLeafPaths(array $map, ?string $currentBasePath): array
    {
        $paths = [];

        foreach ($map as $rule) {
            if (is_array($rule)) {
                array_push($paths, ...self::collectRelativeLeafPaths($rule, $currentBasePath));
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

    protected static function isBlockTypeContainer(array $rule, mixed $sourceValue): bool
    {
        if (! is_array($sourceValue) || array_is_list($sourceValue)) {
            return false;
        }

        return array_any($rule, fn ($_, $childKey) => array_key_exists((string) $childKey, $sourceValue) && is_array($sourceValue[$childKey]) && array_is_list($sourceValue[$childKey]));
    }

    protected static function getPath(array $data, string $path): mixed
    {
        $current = $data;

        foreach (explode('.', $path) as $part) {
            if (! is_array($current) || ! array_key_exists($part, $current)) {
                return null;
            }

            $current = $current[$part];
        }

        return $current;
    }

    protected static function pathExists(array $data, string $path): bool
    {
        $current = $data;

        foreach (explode('.', $path) as $part) {
            if (! is_array($current) || ! array_key_exists($part, $current)) {
                return false;
            }

            $current = $current[$part];
        }

        return true;
    }

    protected static function pathJoin(?string $basePath, string $key): string
    {
        return $basePath === null || $basePath === '' ? $key : $basePath.'.'.$key;
    }
}
