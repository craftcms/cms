<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Import\Importers\BaseImporter;
use Illuminate\Database\Eloquent\Model;

/**
 * @event ItemImported The event that is triggered after data is imported.
 *
 * @since 6.0.0
 */
final readonly class ItemImported
{
    /**
     * Promotes the importer config, imported data, import run ID and the imported element or model into a readonly event payload fired after import.
     *
     * @param  BaseImporter  $importer  The importer config for this event.
     * @param  array<string, mixed>  $data  The imported data.
     * @param  string|null  $runId  The unique ID of the import run this item belongs to, if any.
     * @param  ElementInterface|Model|null  $importedItem  The element or model the data was imported into (also when it was unchanged and not re-saved), if any.
     */
    public function __construct(
        public BaseImporter $importer,
        public array $data,
        public ?string $runId = null,
        public ElementInterface|Model|null $importedItem = null,
    ) {}
}
