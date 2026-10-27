<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Field;

use craft\events\BulkElementsEvent;
use craft\fields\Matrix as LegacyMatrix;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Events\NestedElementsSaved;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use CraftCms\Yii2Adapter\View\LegacyAssets\CpCompatAsset;

class MatrixEntrySaveCompatibility
{
    public function handle(NestedElementsSaved $event): void
    {
        $field = $event->manager->field;
        if (!$field instanceof Matrix) {
            return;
        }

        if ($field instanceof LegacyMatrix) {
            $field->afterSaveEntries(new BulkElementsEvent([
                'elements' => $event->elements,
                'sender' => $event->manager,
            ]));

            return;
        }

        $this->rememberCollapsedEntries($event->elements);
    }

    /** @param list<ElementInterface> $entries */
    public function rememberCollapsedEntries(array $entries): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        $collapsedEntries = array_filter(
            $entries,
            fn(ElementInterface $entry): bool => $entry instanceof Entry && $entry->collapsed,
        );

        if ($collapsedEntries === []) {
            return;
        }

        app(InternalAssetRegistry::class)->flash(CpCompatAsset::class);

        foreach ($collapsedEntries as $entry) {
            $siteId = $entry->siteId ?? 'null';
            session()->flashJs("Craft.MatrixInput.rememberCollapsedEntryId($entry->id, $siteId);", Position::BodyEnd);
        }
    }
}
