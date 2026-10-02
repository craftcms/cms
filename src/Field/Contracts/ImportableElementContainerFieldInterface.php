<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Contracts;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Import\Importers\BaseImporter;
use Illuminate\Validation\Validator;

/**
 * ImportableElementContainerFieldInterface defines the common interface to be implemented by field classes
 * that contain nested elements and wish to support importing content via the import mechanism.
 */
interface ImportableElementContainerFieldInterface extends ElementContainerFieldInterface
{
    /**
     * Normalize the nested entry data for import, applying the specified field layout configuration.
     *
     * @param  array<string, mixed>  $dataItem  The data item to be normalized.
     * @param  FieldLayout  $fieldLayout  The field layout to apply to the data item.
     * @param  array<string, mixed>  $importSettings  The nested fields' import settings, keyed by field handle.
     * @return array<string, mixed>
     */
    public function normalizeNestedEntryForImport(array $dataItem, BaseImporter $importer, FieldLayout $fieldLayout, ?ElementInterface $owner = null, array $importSettings = []): array;

    /**
     * Returns a namespace prefix that is used on the mapping screen.
     */
    public function getMappingUiPrefix(FieldLayout $fieldLayout, mixed $provider = null, ?string $prefix = null): string;

    /**
     * Validates field's mapping.
     *
     * @param  array<string, mixed>  $params
     */
    public function validateMapping(mixed $value, string $attribute, Closure $fail, Validator $validator, array $params = []): bool;

    /**
     * Returns whether this field type supports keeping nested elements missing from imported data at all.
     */
    public function canKeepMissingNestedElements(): bool;

    /**
     * Sets whether nested elements missing from imported data should be kept (not pruned) on the next save.
     *
     * @param  bool  $keep  Whether missing nested elements should be kept.
     */
    public function setKeepMissingNestedElements(bool $keep): void;
}
