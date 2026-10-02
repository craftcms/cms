<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;

/**
 * @return array{section: Section, entryType: EntryType}
 */
function createGraphqlMutationSectionFixture(
    string $sectionName,
    string $sectionHandle,
    string $entryTypeName,
    string $entryTypeHandle,
    SectionType $sectionType = SectionType::Channel,
): array {
    $entryType = EntryType::factory()->create([
        'name' => $entryTypeName,
        'handle' => $entryTypeHandle,
    ]);

    $section = Section::factory()
        ->withEntryTypes($entryType)
        ->create([
            'name' => $sectionName,
            'handle' => $sectionHandle,
            'type' => $sectionType,
        ]);

    EntryTypes::refreshEntryTypes();

    return [
        'section' => $section,
        'entryType' => $entryType,
    ];
}

function createGraphqlMutationEntry(
    Section $section,
    EntryType $entryType,
    string $title,
    string $slug,
): EntryElement {
    return EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement(['title' => $title, 'slug' => $slug]);
}
