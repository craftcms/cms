<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Jobs\UpdateFieldReferences;
use CraftCms\Cms\Field\Contracts\TracksReferencesFieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;

/**
 * Fills `fieldreferences` for existing content. Link fields’ element references used to be stored in `relations`, which
 * `2026_06_02_230524_link_references` removed, and fields only update their references when their value changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(Fields::class)->getAllLayouts()
            ->filter(fn (FieldLayout $layout) => $layout->type && is_subclass_of($layout->type, ElementInterface::class))
            ->filter(fn (FieldLayout $layout) => array_any(
                $layout->getCustomFields(),
                fn ($field) => $field instanceof TracksReferencesFieldInterface,
            ))
            ->groupBy('type')
            ->each(fn (Collection $layouts, string $type) => dispatch(new UpdateFieldReferences(
                $type,
                $layouts->pluck('id')->all(),
            )));
    }

    public function down(): void {}
};
