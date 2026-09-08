<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support;

use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;

final class ImportFixtures
{
    /**
     * Builds a field layout (title + given layout elements), an EntryType wrapping it, a
     * Section for that EntryType, and seeds one Entry through the import-safe factory path.
     */
    public static function seedEntry(
        array $layoutElements,
        array $entryTypeAttrs = [],
        array $sectionAttrs = ['minAuthors' => 0],
        array $entryAttrs = [],
    ): object {
        $fieldLayout = FieldLayout::factory()
            ->withContentTab([
                new EntryTitleField(['uid' => Str::uuid()->toString(), 'required' => true]),
                ...$layoutElements,
            ])
            ->create();

        $entryType = EntryType::factory()
            ->withFieldLayout($fieldLayout)
            ->create(array_merge([
                'name' => 'Seed Type',
                'handle' => 'seedType',
                'hasTitleField' => true,
            ], $entryTypeAttrs));

        $section = Section::factory()->withEntryTypes($entryType)->create($sectionAttrs);

        $result = Entry::factory()
            ->forSection($section)
            ->forEntryType($entryType)
            ->withFieldLayout($fieldLayout)
            ->createElementWithFields(array_merge([
                'title' => 'seed entry',
                'slug' => 'seed-entry',
            ], $entryAttrs));

        return (object) [
            'fieldLayout' => $fieldLayout,
            'entryType' => $entryType,
            'section' => $section,
            'entry' => $result->element,
        ];
    }

    /** A PlainText field with the given handle. */
    public static function plainTextField(string $handle, ?string $name = null): Field
    {
        return Field::factory()->create([
            'name' => $name ?? $handle,
            'handle' => $handle,
            'type' => PlainText::class,
        ]);
    }

    /** An EntryType usable as a Matrix block type, carrying the given fields. */
    public static function blockEntryType(string $handle, array $fields, ?string $name = null): EntryType
    {
        $fieldLayout = FieldLayout::factory()
            ->withContentTab(array_map(fn (Field $field) => new CustomField(config: ['fieldUid' => $field->uid]), $fields))
            ->create();

        return EntryType::factory()
            ->withFieldLayout($fieldLayout)
            ->create(['name' => $name ?? $handle, 'handle' => $handle, 'hasTitleField' => true]);
    }

    /** A Matrix field restricted to the given block entry types. Refreshes entry-type/field caches. */
    public static function matrixField(string $handle, array $entryTypes, ?string $name = null): Field
    {
        $field = Field::factory()->create([
            'name' => $name ?? $handle,
            'handle' => $handle,
            'type' => Matrix::class,
            'settings' => ['entryTypes' => array_map(fn (EntryType $et) => $et->id, $entryTypes)],
        ]);

        EntryTypes::refreshEntryTypes();
        Fields::refreshFields();

        return $field;
    }
}
