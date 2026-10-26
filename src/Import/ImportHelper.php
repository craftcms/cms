<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\ImportableElementContainerFieldInterface;
use CraftCms\Cms\FieldLayout\Contracts\ImportableFieldLayoutElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Import\Data\CompoundMappingColumn;
use CraftCms\Cms\Import\Data\MappingColumn;
use CraftCms\Cms\Import\Data\MappingTemplate;
use CraftCms\Cms\Import\Data\SourceColumn;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Attributes\Importable;
use CraftCms\Cms\Support\Facades\Import;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Ui\Controls\Concerns\HasTextExpander;

use function CraftCms\Cms\renderSandboxedObjectTemplate;
use function CraftCms\Cms\t;

/**
 * @phpstan-type ImportableProperty array{property: string, name: string, label: string, excludeFromUiMapping: bool, isContainer: bool, canBeMatchCriteria: bool, canBeCleared: bool, canBeSet: bool, defaultValue: mixed, trait: class-string|null}
 * @phpstan-type MappedNode array{data: array<mixed>, consumed: list<int|string>}
 * @phpstan-type MappingSource array{value: array<mixed>, basePath: string, consumed: list<string>}
 * @phpstan-type MappedScalar array{value: mixed, consumed: string|null}
 * @phpstan-type MappableLeaf array{path: string, segments: list<string>}
 * @phpstan-type IncomingCol array{segments: list<string>, value: string}
 *
 * @phpstan-import-type TextExpanderTrigger from HasTextExpander
 *
 * @since 6.0.0
 */
class ImportHelper
{
    /**
     * The `$map` leaf that marks a destination as filled from its object template in `BaseImporter::$templates`.
     */
    public const string TEMPLATE_RULE = '__template__';

    /**
     * Keys the incoming data can use to steer the import itself, so they're never mapped or read by a template.
     */
    public const array RESERVED_DATA_KEYS = ['matchCriteria', 'clearableItems', 'keepMissingNestedElements'];

    /**
     * Returns whether any segment of an incoming data path is a reserved key, e.g. `blocks.matchCriteria.title`.
     */
    public static function isReservedDataPath(string $path): bool
    {
        return array_intersect(explode('.', $path), self::RESERVED_DATA_KEYS) !== [];
    }

    /**
     * @var array<string, string> Keys prepped for auto-matching, indexed by the original key.
     */
    private static array $autoMatchingKeys = [];

    /**
     * @var array<class-string, list<ImportableProperty>> Importable properties, indexed by the class they belong to.
     */
    private static array $importableProperties = [];

    public static function prepKeyForAutoMatching(string $key): string
    {
        if (isset(self::$autoMatchingKeys[$key])) {
            return self::$autoMatchingKeys[$key];
        }

        $fragments = explode('.', $key);
        foreach ($fragments as $i => $fragment) {
            $fragments[$i] = Str::toHandle(implode(' ', Str::toWords($fragment)));
        }

        return self::$autoMatchingKeys[$key] = Arr::join($fragments, '.');
    }

    /**
     * Normalizes match criteria coming from an importer config (UI or a registered import plan)
     * into an array where keys are the fields/attributes/properties to update,
     * and values containing the incoming data keys.
     *
     * @return array<mixed>
     */
    public static function normalizeMatchCriteriaFromImporterConfig(BaseImporter $importer): array
    {
        // for the same key, the order of importance is (see Import::importItem()):
        // matchCriteria coming from the incoming data, are merged with and overwritten by
        // matchCriteria coming from the UI or a registered import plan (those two never exist together), are merged with and overwritten by
        // the BaseTransformer::additionalMatchCriteria() values (if custom transformer is specified)

        // get the map
        $map = $importer->map;

        // get the matchCriteria that are coming from the UI or from a registered import plan
        $matchCriteria = $importer->matchCriteria ?? [];

        // ones coming from the UI will have a value of 1
        // ones coming from a registered import plan should be strings that point to the original data keys

        // for the ones coming from the UI - we need to resolve those to the mapped column names;
        // a field that isn't mapped has nothing to match on, so its criterion is dropped
        $dottedMap = Arr::dot($map);
        $dottedMatchCriteria = Arr::dot($matchCriteria);

        foreach ($dottedMatchCriteria as $key => $value) {
            if (is_numeric($value) && $value == 1) {
                if (in_array($dottedMap[$key] ?? null, [null, ''], true)) {
                    unset($dottedMatchCriteria[$key]);
                } else {
                    $dottedMatchCriteria[$key] = $dottedMap[$key];
                }
            }
        }

        return Arr::undot($dottedMatchCriteria);
    }

    /**
     * Returns the properties of the importer’s target class that are marked as importable.
     *
     * @return list<ImportableProperty>
     */
    public static function getImportableProperties(BaseImporter $importer): array
    {
        $targetClass = $importer::targetClass();

        if (isset(self::$importableProperties[$targetClass])) {
            return self::$importableProperties[$targetClass];
        }

        // Automatically include all Importable properties, including inherited ones
        // such as parent from Structurable trait on Element.
        $class = new \ReflectionClass($targetClass);
        $properties = [];

        do {
            foreach ($class->getProperties() as $property) {
                if ($property->getAttributes(Importable::class) === []) {
                    continue;
                }

                $properties[$property->getName()] ??= $property;
            }
        } while ($class = $class->getParentClass());

        return self::$importableProperties[$targetClass] = array_values(array_map(function ($property) {
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
                'canBeSet' => $arguments[6] ?? $arguments['canBeSet'] ?? true,
                'defaultValue' => $property->getDefaultValue(),
                'trait' => self::declaringTrait($property->getDeclaringClass(), $property->getName()),
            ];
        }, $properties));
    }

    /**
     * Returns the trait that declares the given property, if any, including traits used by other traits.
     *
     * @param  \ReflectionClass<object>  $class
     */
    private static function declaringTrait(\ReflectionClass $class, string $propertyName): ?string
    {
        foreach ($class->getTraits() as $trait) {
            if (! $trait->hasProperty($propertyName)) {
                continue;
            }

            return self::declaringTrait($trait, $propertyName) ?? $trait->getName();
        }

        return null;
    }

    /**
     * Returns the importable properties of the importer’s target class that are containers.
     *
     * @return list<ImportableProperty>
     */
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

    /**
     * Normalizes an incoming value for a field: first by the field itself, then by its registered import handler, if any.
     *
     * @param  array<string, mixed>  $importSettings  the field's settings from the importer's `fieldSettings` tree
     */
    public static function normalizeFieldValueForImport(
        FieldInterface $field,
        mixed $value,
        BaseImporter $importer,
        ?ElementInterface $rootOwner = null,
        array $importSettings = [],
    ): mixed {
        $value = $field->normalizeValueForImport($value, $importer, $rootOwner, $importSettings);
        $handler = Import::getFieldImportHandlerFor($field);

        return $handler ? $handler->normalizeValue($field, $value, $importer, $rootOwner, $importSettings) : $value;
    }

    /**
     * Returns the mapping columns for each importable element in the given field layout.
     *
     * @return list<MappingColumn|CompoundMappingColumn>
     */
    public static function getDestinationColsForFieldLayout(
        ?FieldLayout $fieldLayout,
        ?FieldInterface $ownerField = null,
        mixed $provider = null,
        ?string $prefix = null
    ): array {
        if (! $fieldLayout) {
            return [];
        }

        $cols = [];

        foreach ($fieldLayout->getAllElements() as $fieldLayoutElement) {
            // e.g. lat/long offers two columns, a custom field one, and a missing custom field none
            if ($fieldLayoutElement instanceof ImportableFieldLayoutElementInterface) {
                $col = $fieldLayoutElement->getFieldsForMapping($fieldLayout, $ownerField, $provider, $prefix);

                if ($col !== null) {
                    $cols[] = $col;
                }
            }
        }

        return $cols;
    }

    /**
     * Recursively decodes any JSON-encoded strings found inside the given value, leaving
     * already-decoded values (and strings that aren't valid JSON) untouched.
     */
    public static function decodeRecursive(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(self::decodeRecursive(...), $value);
        }

        if (! is_string($value)) {
            return $value;
        }

        if (! Str::isJson($value)) {
            return $value;
        }

        return Json::decode($value);
    }

    /**
     * Recursively checks whether every value in the given array is empty (null or an empty string),
     * treating a nested array as empty only if all of its own values are empty too.
     *
     * @param  array<mixed>  $data
     */
    public static function isEmptyImportEntryData(array $data): bool
    {
        foreach ($data as $value) {
            if (is_array($value)) {
                if (! self::isEmptyImportEntryData($value)) {
                    return false;
                }

                continue;
            }

            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the prefixed handle a mapping column is addressed by, accounting for the container field
     * (and its field layout provider) the column sits in, if any.
     */
    public static function prefixedHandleForMapping(
        string $attribute,
        ?FieldInterface $ownerField,
        mixed $field,
        ?FieldLayout $fieldLayout,
        mixed $provider,
        ?string $prefix = null,
    ): string {
        if ($ownerField instanceof ImportableElementContainerFieldInterface) {
            $namePrefix = $ownerField->getMappingUiPrefix($fieldLayout, $provider, $prefix);

            return $field instanceof FieldInterface
                ? $namePrefix."[fields][$attribute]"
                : $namePrefix."[$attribute]";
        }

        return ! empty($prefix) ? $prefix."[$attribute]" : $attribute;
    }

    /**
     * Replaces each layout element uid key in one level of a mapping tree with that field’s current handle,
     * and does the same inside container fields.
     *
     * Mappings use layout element uids so they keep working after a handle override changes.
     * Keys that aren’t layout element uids are left unchanged.
     *
     * @param  array<mixed>  $tree
     * @return array<mixed>
     */
    public static function resolveMappingKeys(array $tree, ?FieldLayout $fieldLayout): array
    {
        if (! $fieldLayout) {
            return $tree;
        }

        $layoutElements = [];
        foreach ($fieldLayout->getCustomFieldElements() as $layoutElement) {
            if ($layoutElement->uid !== null) {
                $layoutElements[$layoutElement->uid] = $layoutElement;
            }
        }

        $resolved = [];

        foreach ($tree as $key => $value) {
            $layoutElement = $layoutElements[$key] ?? null;

            if (! $layoutElement) {
                $resolved[$key] = $value;

                continue;
            }

            $field = $layoutElement->getField();

            $resolved[$layoutElement->attribute()] = $field instanceof ImportableElementContainerFieldInterface && is_array($value)
                ? $field->resolveMappingKeys($value)
                : $value;
        }

        return $resolved;
    }

    /**
     * Rebuilds raw import data into the shape described by the map.
     *
     * @param  array<mixed>  $map
     * @param  array<mixed>  $data
     * @param  array<mixed>  $templates  the object templates for the map's `TEMPLATE_RULE` leaves, keyed like the map
     * @return array<mixed>
     */
    public static function remapData(array $map, array $data, array $templates = []): array
    {
        return self::mapNode(self::withTemplates($map, $templates), $data, $data, $data, null, true)['data'];
    }

    /**
     * Swaps each `TEMPLATE_RULE` leaf in the map for the template it's filled from.
     *
     * @param  array<mixed>  $map
     * @param  array<mixed>  $templates
     * @return array<mixed>
     */
    protected static function withTemplates(array $map, array $templates): array
    {
        foreach ($map as $key => $rule) {
            $template = $templates[$key] ?? null;

            if (is_array($rule)) {
                $map[$key] = self::withTemplates($rule, is_array($template) ? $template : []);
            } elseif ($rule === self::TEMPLATE_RULE) {
                $map[$key] = new MappingTemplate(is_string($template) ? $template : '');
            }
        }

        return $map;
    }

    /**
     * Maps one level of `$map` against `$currentData`, following each rule and recursing into
     * nested maps or lists.
     *
     * `$templateData` is what this level's object templates are rendered against: the root data,
     * with each list being mapped swapped for its item that's being mapped.
     *
     * @param  array<mixed>  $map
     * @param  array<mixed>  $rootData
     * @param  array<mixed>  $templateData
     * @return MappedNode
     */
    protected static function mapNode(
        array $map,
        array $rootData,
        array $templateData,
        mixed $currentData,
        ?string $currentBasePath,
        bool $keepUnused
    ): array {
        $result = [];
        $consumedKeys = [];
        // keys explicitly left unmapped, so an incoming key of the same name isn't passed through to them either
        $skippedKeys = [];

        foreach ($map as $targetKey => $rule) {
            // The rule is itself a nested map, so work out what part of the data it should read from.
            if (is_array($rule)) {
                $source = self::findSourceForRule((string) $targetKey, $rule, $rootData, $currentData, $currentBasePath);

                if ($source === null) {
                    // No direct source found, so recurse into the map as a plain nested object instead.
                    $nested = self::mapNode($rule, $rootData, $templateData, $currentData, $currentBasePath, false);
                    $result[$targetKey] = $nested['data'];
                    array_push($consumedKeys, ...$nested['consumed']);

                    continue;
                }

                array_push($consumedKeys, ...$source['consumed']);

                if (self::blockLooksGroupedByType($rule, $source['value'])) {
                    // The source groups rows by block type, e.g. {typeA: [...], typeB: [...]}.
                    $mapped = self::mapGroupedBlocksByType($rule, $rootData, $templateData, $source['value'], $source['basePath']);
                    $result[$targetKey] = $mapped['data'];
                    array_push($consumedKeys, ...$mapped['consumed']);
                } elseif (array_is_list($source['value'])) {
                    // The source is a flat list, so map each row on its own.
                    $result[$targetKey] = array_map(
                        fn ($row) => is_array($row)
                            ? self::mapRowByOwnType($rule, $rootData, self::templateDataFor($templateData, $source['basePath'], $row), $row, $source['basePath'])
                            : $row,
                        $source['value']
                    );
                } else {
                    // The source is a single nested object, so map it directly.
                    $result[$targetKey] = self::mapNode($rule, $rootData, $templateData, $source['value'], $source['basePath'], true)['data'];
                }

                continue;
            }

            if ($rule === null) {
                // A null rule means: don't set this field at all.
                $skippedKeys[] = $targetKey;

                continue;
            }

            if ($rule === '""') {
                // '""' is a special marker meaning: set this field to an empty string.
                $result[$targetKey] = '';

                continue;
            }

            if ($rule instanceof MappingTemplate) {
                $variables = self::withoutReservedKeys($templateData);
                $result[$targetKey] = renderSandboxedObjectTemplate($rule->template, $variables, $variables);

                foreach ($rule->paths as $path) {
                    $consumed = self::consumedKey($path, $currentBasePath);

                    if ($consumed !== null) {
                        $consumedKeys[] = $consumed;
                    }
                }

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
                if (! in_array($key, $consumedKeys, true) && ! in_array($key, $skippedKeys, true) && ! array_key_exists($key, $result)) {
                    $result[$key] = $value;
                }
            }
        }

        return [
            'data' => $result,
            'consumed' => array_values(array_unique($consumedKeys)),
        ];
    }

    /**
     * Works out what raw data a nested rule should read from, and whether it's a list of rows,
     * a type-grouped set, or a single group.
     *
     * @param  array<mixed>  $rule
     * @param  array<mixed>  $rootData
     * @return MappingSource|null
     */
    protected static function findSourceForRule(
        string $targetKey,
        array $rule,
        array $rootData,
        mixed $currentData,
        ?string $currentBasePath
    ): ?array {
        // If the current data already has a key with this exact name, use it directly.
        if (is_array($currentData) && array_key_exists($targetKey, $currentData) && is_array($currentData[$targetKey])) {
            return [
                'value' => $currentData[$targetKey],
                'basePath' => self::pathJoin($currentBasePath, $targetKey),
                'consumed' => [$targetKey],
            ];
        }

        // Otherwise, work out the shared path implied by the rule's own values.
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

        // Try the current data first, then fall back to the root data.
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
     *
     * @param  array<mixed>  $data
     * @return MappingSource|null
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
     *
     * @param  array<mixed>  $rule
     * @param  array<mixed>  $rootData
     * @param  array<mixed>  $templateData
     * @param  array<mixed>  $row
     * @return array<mixed>
     */
    protected static function mapRowByOwnType(array $rule, array $rootData, array $templateData, array $row, ?string $basePath): array
    {
        if (array_key_exists('type', $row) && is_string($row['type']) && array_key_exists($row['type'], $rule) && is_array($rule[$row['type']])) {
            return self::mapRowForType($row['type'], $rule[$row['type']], $rootData, $templateData, $row, $basePath);
        }

        return self::mapNode($rule, $rootData, $templateData, $row, $basePath, true)['data'];
    }

    /**
     * Maps a raw value that's grouped by block type (each type holding its own list of rows)
     * into one flat list of mapped rows.
     *
     * @param  array<mixed>  $blockTypeMap
     * @param  array<mixed>  $rootData
     * @param  array<mixed>  $templateData
     * @param  array<mixed>  $sourceValue
     * @return MappedNode
     */
    protected static function mapGroupedBlocksByType(array $blockTypeMap, array $rootData, array $templateData, array $sourceValue, string $basePath): array
    {
        $items = [];
        $consumedKeys = [];

        foreach ($blockTypeMap as $type => $typeMap) {
            // Skip a type if the source doesn't have a matching list of rows for it.
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
                    $items[] = self::mapRowForType((string) $type, $typeMap, $rootData, self::templateDataFor($templateData, $typeBasePath, $row), $row, $typeBasePath);
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
     *
     * @param  array<mixed>  $typeMap
     * @param  array<mixed>  $rootData
     * @param  array<mixed>  $templateData
     * @param  array<mixed>  $row
     * @return array<mixed>
     */
    protected static function mapRowForType(string $type, array $typeMap, array $rootData, array $templateData, array $row, ?string $basePath): array
    {
        return ['type' => $type] + self::mapNode($typeMap, $rootData, $templateData, $row, $basePath, true)['data'];
    }

    /**
     * Returns the data to render object templates against for one item of the list at `$basePath`:
     * the data so far, with that list swapped for the item, so `{list.key}` reads the item's own value.
     *
     * @param  array<mixed>  $templateData
     * @param  array<mixed>  $item
     * @return array<mixed>
     */
    protected static function templateDataFor(array $templateData, string $basePath, array $item): array
    {
        Arr::set($templateData, $basePath, $item);

        return $templateData;
    }

    /**
     * Returns the data with its reserved keys removed at every depth.
     *
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    protected static function withoutReservedKeys(array $data): array
    {
        $data = Arr::except($data, self::RESERVED_DATA_KEYS);

        return array_map(fn (mixed $value) => is_array($value) ? self::withoutReservedKeys($value) : $value, $data);
    }

    /**
     * Returns the key a data path consumes at the current level: its first segment below the current
     * base path, or below the root when we're at the root. A path elsewhere consumes nothing.
     */
    protected static function consumedKey(string $path, ?string $currentBasePath): ?string
    {
        if ($currentBasePath !== null && str_starts_with($path, $currentBasePath.'.')) {
            return explode('.', substr($path, strlen($currentBasePath) + 1))[0];
        }

        return $currentBasePath === null ? explode('.', $path)[0] : null;
    }

    /**
     * Reads a single value out of the data for a plain (non-array) rule.
     *
     * @param  array<mixed>  $rootData
     * @return MappedScalar
     */
    protected static function mapScalarValue(string $path, array $rootData, mixed $currentData, ?string $currentBasePath): array
    {
        // The rule path is relative to where we are, so strip the shared prefix off first.
        if ($currentBasePath !== null && str_starts_with($path, $currentBasePath.'.')) {
            $relativePath = substr($path, strlen($currentBasePath) + 1);

            return [
                'value' => is_array($currentData) ? self::findPath($currentData, $relativePath)['value'] : null,
                'consumed' => self::consumedKey($path, $currentBasePath),
            ];
        }

        // The rule path didn't match our position, so read it straight from the root.
        return [
            'value' => self::findPath($rootData, $path)['value'],
            'consumed' => self::consumedKey($path, $currentBasePath),
        ];
    }

    /**
     * Finds the parent path that all of a rule's leaf values share, so a nested map can be resolved
     * to one source.
     *
     * The source is what holds the leaf values, so a leaf's own last segment is never part of it;
     * that way a rule with a single leaf (`items.name`) resolves to `items` just as one with several does.
     *
     * @param  array<mixed>  $map
     */
    protected static function sharedSourcePath(array $map, ?string $currentBasePath): ?string
    {
        $parents = array_map(
            fn (string $path): array => array_slice(explode('.', $path), 0, -1),
            self::collectLeafPaths($map, $currentBasePath),
        );

        if ($parents === []) {
            return null;
        }

        $common = array_shift($parents);

        // Narrow the shared path down to what every leaf's parent path has in common.
        foreach ($parents as $parts) {
            $next = [];

            for ($i = 0, $max = min(count($common), count($parts)); $i < $max; $i++) {
                if ($common[$i] !== $parts[$i]) {
                    break;
                }

                $next[] = $common[$i];
            }

            $common = $next;
        }

        // If part of the shared path is itself a key in the map, stop there — that key needs its own resolution.
        foreach ($common as $index => $part) {
            if (array_key_exists($part, $map)) {
                return $index === 0 ? null : implode('.', array_slice($common, 0, $index));
            }
        }

        return implode('.', $common);
    }

    /**
     * Collects every leaf rule path inside a map, relative to the current base path.
     * A template contributes the paths it reads.
     *
     * @param  array<mixed>  $map
     * @return list<string>
     */
    protected static function collectLeafPaths(array $map, ?string $currentBasePath): array
    {
        $paths = [];

        foreach ($map as $rule) {
            if (is_array($rule)) {
                array_push($paths, ...self::collectLeafPaths($rule, $currentBasePath));

                continue;
            }

            $rulePaths = match (true) {
                $rule instanceof MappingTemplate => $rule->paths,
                is_string($rule) => [$rule],
                default => [],
            };

            foreach ($rulePaths as $path) {
                // Only keep leaf paths that fall under the current base path (or all of them, if we're at the root).
                if ($currentBasePath !== null && str_starts_with($path, $currentBasePath.'.')) {
                    $paths[] = substr($path, strlen($currentBasePath) + 1);
                } elseif ($currentBasePath === null) {
                    $paths[] = $path;
                }
            }
        }

        return $paths;
    }

    /**
     * Checks whether a raw value looks like it's grouped by block type, rather than being a
     * block's own set of fields.
     *
     * @param  array<mixed>  $rule
     */
    protected static function blockLooksGroupedByType(array $rule, mixed $sourceValue): bool
    {
        // A flat list of rows (each with its own type) isn't grouped by type.
        if (! is_array($sourceValue) || array_is_list($sourceValue)) {
            return false;
        }

        // A genuine block-type map only ever declares type submaps at its top level; a mix of
        // scalar/null field rules alongside a submap means this is a block's own fields map
        // (one of which happens to be a nested container field), not a type-dispatch map.
        if (array_any($rule, fn ($childRule) => ! is_array($childRule))) {
            return false;
        }

        return array_any($rule, fn ($_, $childKey) => self::sourceIsRowListForType((string) $childKey, $sourceValue[$childKey] ?? null));
    }

    /**
     * Checks whether a source value is a list of rows belonging to the given block type,
     * rather than a nested container field's own list of inline-typed rows.
     */
    protected static function sourceIsRowListForType(string $type, mixed $sourceValue): bool
    {
        if (! is_array($sourceValue) || ! array_is_list($sourceValue)) {
            return false;
        }

        // Rows in a genuine type group either carry no type of their own or repeat the group's;
        // a row declaring a different type means the key is a nested container field handle.
        return ! array_any($sourceValue, fn ($row) => is_array($row) && is_string($row['type'] ?? null) && $row['type'] !== $type);
    }

    /**
     * Walks a dotted `$path` inside `$data`, returning whether it was found and its value.
     *
     * @param  array<mixed>  $data
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

    /**
     * Joins a base path and a key into one dotted path.
     */
    protected static function pathJoin(?string $basePath, string $key): string
    {
        return $basePath === null || $basePath === '' ? $key : $basePath.'.'.$key;
    }

    /**
     * Returns the text expander triggers that offer the source columns as variables in a mapping template,
     * the same way `SelectOptions::getObjectTemplateTextExpanderTriggers()` offers an object's properties.
     *
     * A column whose path isn't made of plain identifiers can't be written as `{path}`, so it's offered as
     * a print tag that reads it off `object` instead, e.g. `{{ object['First Name'] }}`.
     *
     * @param  list<SourceColumn>  $sourceDataCols
     * @return list<TextExpanderTrigger>
     */
    public static function templateTextExpanderTriggers(array $sourceDataCols): array
    {
        $options = [];

        foreach ($sourceDataCols as $sourceCol) {
            if (in_array($sourceCol->value, ['', self::TEMPLATE_RULE], true)) {
                continue;
            }

            $value = self::templateVariable($sourceCol->value);

            $options[] = [
                'label' => $sourceCol->label,
                'value' => $value,
                'keywords' => [$sourceCol->value],
                'data' => ['hint' => $value],
            ];
        }

        usort($options, fn (array $a, array $b): int => strnatcasecmp($a['label'], $b['label']));

        return [[
            'trigger' => '{',
            'boundary' => 'anywhere',
            'label' => t('Object Template Variables'),
            'options' => $options,
        ]];
    }

    /**
     * Returns how a template reads the incoming data at `$path`, e.g. `title` is `{title}`.
     *
     * Shorthand only works with plain identifiers, so any other path reads its value off `object` in a print tag,
     * e.g. `author.First Name` is `{{ object.author['First Name'] }}`.
     */
    private static function templateVariable(string $path): string
    {
        $segments = explode('.', $path);
        $isIdentifier = fn (string $segment): bool => preg_match('/^\w+$/', $segment) === 1;

        if (array_all($segments, $isIdentifier)) {
            return sprintf('{%s}', $path);
        }

        $reference = implode('', array_map(
            fn (string $segment): string => $isIdentifier($segment) ? ".$segment" : sprintf("['%s']", addcslashes($segment, "'\\")),
            $segments,
        ));

        return sprintf('{{ object%s }}', $reference);
    }

    /**
     * Returns a best-guess incoming column for each of `$map`'s unmapped leaves, as a tree keyed
     * the same way as the map itself. It uses the same normalized-handle matching as the
     * auto-magic mapping done at import time.
     *
     * Matching is done on a column's full path rather than just its leaf handle, so the most
     * specific incoming column wins: given both `title` and `matrixOuter.title` headings, the
     * former fills the top-level Title and the latter the Title inside the matrix. A column can
     * still fall back to a heading that names less of its path, but a heading claimed by a
     * full-path match is never offered as a fallback.
     *
     * @param  list<MappingColumn|CompoundMappingColumn>  $destinationCols
     * @param  list<SourceColumn>  $sourceDataCols
     * @param  array<mixed>  $map  The current (possibly partially saved) map tree.
     * @param  BaseImporter|null  $importer  The importer used to convert uids in the mapping paths to handles before matching.
     * @return array<mixed>
     */
    public static function suggestMapValues(array $destinationCols, array $sourceDataCols, array $map, ?BaseImporter $importer = null): array
    {
        $incomingCols = [];
        foreach ($sourceDataCols as $sourceCol) {
            if (in_array($sourceCol->value, ['', self::TEMPLATE_RULE], true)) {
                continue;
            }

            $segments = self::matchSegments($sourceCol->value);

            if ($segments === []) {
                continue;
            }

            $incomingCols[implode('.', $segments)] = ['segments' => $segments, 'value' => $sourceCol->value];
        }

        $leaves = [];
        self::collectMappableLeaves($destinationCols, $map, $leaves);

        if ($importer) {
            $leaves = self::matchLeavesOnHandles($leaves, $importer);
        }

        $suggestions = [];
        self::assignSuggestions($leaves, array_values($incomingCols), $suggestions);

        return $suggestions;
    }

    /**
     * Updates each leaf so it’s matched on handles instead of uids, because the incoming data uses
     * field and entry type handles.
     *
     * @param  list<MappableLeaf>  $leaves
     * @return list<MappableLeaf>
     */
    private static function matchLeavesOnHandles(array $leaves, BaseImporter $importer): array
    {
        $tree = [];
        foreach ($leaves as $leaf) {
            Arr::set($tree, $leaf['path'], $leaf['path']);
        }

        $resolvedPaths = array_flip(Arr::dot($importer->resolveMappingKeys($tree)));

        foreach ($leaves as $i => $leaf) {
            $resolvedPath = $resolvedPaths[$leaf['path']] ?? null;

            if (is_string($resolvedPath)) {
                $leaves[$i]['segments'] = self::matchSegments($resolvedPath);
            }
        }

        return $leaves;
    }

    /**
     * Normalizes a key into the segments it should be auto-matched on. They're lowercased so a
     * heading can be spelled however its author liked — `matrixouter`, `matrix-inner` and
     * `plain-text` all line up with the handles they name.
     *
     * @param  string|list<string>  $key
     * @return list<string>
     */
    private static function matchSegments(string|array $key): array
    {
        $segments = is_array($key) ? $key : explode('.', $key);

        foreach ($segments as $i => $segment) {
            $segments[$i] = Str::lower(Str::toHandle(implode(' ', Str::toWords((string) $segment))));
        }

        return array_values(array_filter($segments, fn (string $segment) => $segment !== ''));
    }

    /**
     * Returns how many of an incoming column's segments line up with a destination's, or `null`
     * if the column doesn't describe that destination at all.
     *
     * The column has to name the destination's own handle, and everything in front of that has
     * to appear in order — but not necessarily back to back, because a destination path also
     * carries segments the data never does, such as a nested entry's entry type handle.
     *
     * @param  list<string>  $destinationSegments
     * @param  list<string>  $sourceSegments
     */
    private static function matchScore(array $destinationSegments, array $sourceSegments): ?int
    {
        $score = count($sourceSegments);

        if ($score === 0 || $score > count($destinationSegments)) {
            return null;
        }

        if (array_pop($sourceSegments) !== array_pop($destinationSegments)) {
            return null;
        }

        foreach ($sourceSegments as $segment) {
            $at = array_search($segment, $destinationSegments, true);

            if ($at === false) {
                return null;
            }

            $destinationSegments = array_slice($destinationSegments, $at + 1);
        }

        return $score;
    }

    /**
     * Collects every destination leaf that still needs a value, keeping each one's map path
     * alongside the normalized segments it should be matched on.
     *
     * @param  list<MappingColumn|CompoundMappingColumn>  $destinationCols
     * @param  array<mixed>  $map
     * @param  list<MappableLeaf>  $leaves
     */
    private static function collectMappableLeaves(array $destinationCols, array $map, array &$leaves): void
    {
        foreach ($destinationCols as $col) {
            if ($col instanceof CompoundMappingColumn) {
                self::collectMappableLeaves($col->subfields, $map, $leaves);

                continue;
            }

            if ($col->prefixedHandleAsArray === [] || $col->isContainer) {
                continue;
            }

            $path = implode('.', $col->prefixedHandleAsArray);

            if (Arr::get($map, $path, '') !== '') {
                continue;
            }

            $leaves[] = [
                'path' => $path,
                'segments' => self::matchSegments($col->prefixedHandleAsArray),
            ];
        }
    }

    /**
     * Scores each leaf against every incoming column that describes it, then assigns the most
     * specific matches first.
     *
     * @param  list<MappableLeaf>  $leaves
     * @param  list<IncomingCol>  $incomingCols
     * @param  array<mixed>  $suggestions
     */
    private static function assignSuggestions(array $leaves, array $incomingCols, array &$suggestions): void
    {
        $candidates = [];

        foreach ($leaves as $i => $leaf) {
            $segmentCount = count($leaf['segments']);

            foreach ($incomingCols as $incomingCol) {
                $score = self::matchScore($leaf['segments'], $incomingCol['segments']);

                if ($score === null) {
                    continue;
                }

                $candidates[] = [
                    'leaf' => $i,
                    'value' => $incomingCol['value'],
                    'full' => $score === $segmentCount,
                    'length' => $score,
                ];
            }
        }

        // most specific first: full-path matches, then the longest tail match, then
        // destination order so the result doesn't depend on sort stability
        usort(
            $candidates,
            fn (array $a, array $b) => [$b['full'], $b['length'], $a['leaf']] <=> [$a['full'], $a['length'], $b['leaf']],
        );

        $assigned = [];
        $claimed = [];

        foreach ($candidates as $candidate) {
            if (isset($assigned[$candidate['leaf']])) {
                continue;
            }

            // a heading that exactly matches some other column's whole path belongs to it
            if (! $candidate['full'] && isset($claimed[$candidate['value']])) {
                continue;
            }

            $assigned[$candidate['leaf']] = true;

            if ($candidate['full']) {
                $claimed[$candidate['value']] = true;
            }

            Arr::set($suggestions, $leaves[$candidate['leaf']]['path'], $candidate['value']);
        }
    }
}
