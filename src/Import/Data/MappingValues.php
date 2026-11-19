<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Data;

use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Import\Importers\BaseImporter;
use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

/**
 * The parallel trees an import step's mapping screen edits, all keyed alike.
 *
 * @since 6.0.0
 */
readonly class MappingValues implements JsonSerializable
{
    /**
     * @param  array<array-key, mixed>  $map
     * @param  array<array-key, mixed>  $matchCriteria
     * @param  array<array-key, mixed>  $clearableItems
     * @param  array<array-key, mixed>  $defaultValues
     * @param  array<array-key, mixed>  $keepMissingNestedElements
     * @param  array<array-key, mixed>  $fieldSettings
     * @param  array<array-key, mixed>  $templates
     */
    public function __construct(
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $map = [],
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $matchCriteria = [],
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $clearableItems = [],
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $keepMissingNestedElements = [],
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $defaultValues = [],
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $fieldSettings = [],
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $templates = [],
        /** What a step does with rows that match an existing element; null for an importer that doesn't match. */
        public ?string $matchMode = null,
    ) {}

    /**
     * Returns the mapping state an importer holds, as the mapping screen edits it.
     */
    public static function fromImporter(BaseImporter $importer): self
    {
        // the mapping screen keys custom fields by uid, so a mapping written with handles shows there too
        $keys = $importer->uidMappingKeys(...);

        return new self(
            map: $keys($importer->map),
            matchCriteria: $keys($importer->matchCriteria ?? []),
            clearableItems: $keys($importer->clearableItems ?? []),
            defaultValues: $keys($importer->defaultValues ?? []),
            keepMissingNestedElements: $importer instanceof ElementImporter ? $keys($importer->keepMissingNestedElements ?? []) : [],
            fieldSettings: $importer instanceof ElementImporter ? $keys($importer->fieldSettings ?? []) : [],
            templates: $keys($importer->templates ?? []),
            matchMode: $importer instanceof ElementImporter ? $importer->matchMode->value : null,
        );
    }

    /** @return array<string, array<array-key, mixed>|string|null> */
    public function jsonSerialize(): array
    {
        return [
            'map' => $this->map,
            'matchCriteria' => $this->matchCriteria,
            'clearableItems' => $this->clearableItems,
            'defaultValues' => $this->defaultValues,
            'keepMissingNestedElements' => $this->keepMissingNestedElements,
            'fieldSettings' => $this->fieldSettings,
            'templates' => $this->templates,
            'matchMode' => $this->matchMode,
        ];
    }
}
