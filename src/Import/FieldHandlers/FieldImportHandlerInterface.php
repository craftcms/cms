<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\FieldHandlers;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Import\Data\FieldMappingSetting;
use CraftCms\Cms\Import\Importers\BaseImporter;

/**
 * Does the import-specific work for a field type, without the field itself having to know about imports.
 *
 * A handler does its work as the incoming value is normalized, e.g. turning referenced files into assets.
 * Side effects that should only happen if the item goes through can be queued with {@see BaseImporter::afterItemImported()}.
 * Handlers are registered with {@see FieldImportHandlers}, keyed by the field class they handle.
 *
 * @since 6.0.0
 */
interface FieldImportHandlerInterface
{
    /**
     * Returns the field class this handler handles. Subclasses of it use the handler too,
     * unless a handler is registered for the subclass itself.
     *
     * @return class-string<FieldInterface>
     */
    public static function fieldClass(): string;

    /**
     * Normalizes an incoming value further, after the field’s own `normalizeValueForImport()` has run.
     *
     * @param  array<string, mixed>  $importSettings  the field's settings from the importer's `fieldSettings` tree
     */
    public function normalizeValue(FieldInterface $field, mixed $value, BaseImporter $importer, ?ElementInterface $rootOwner = null, array $importSettings = []): mixed;

    /**
     * Returns the extra settings that can be set for the field when it’s mapped in an import.
     * A setting’s optional `instructions` are shown in an info tooltip beside its label.
     *
     * @return list<FieldMappingSetting>
     */
    public function mappingSettings(FieldInterface $field): array;
}
