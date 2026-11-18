<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Elements\ContentBlock as ContentBlockElement;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

function createContentBlockSection(): array
{
    $innerField = Field::factory()->create(['handle' => 'blockText', 'type' => PlainText::class]);
    Fields::refreshFields();
    $contentBlockField = Field::factory()->create([
        'handle' => 'testBlock',
        'type' => ContentBlock::class,
        'settings' => [
            'fieldLayouts' => [
                Str::uuid()->toString() => [
                    'tabs' => [
                        [
                            'uid' => Str::uuid()->toString(),
                            'name' => 'Content',
                            'elements' => [
                                [
                                    'uid' => Str::uuid()->toString(),
                                    'type' => CustomField::class,
                                    'fieldUid' => $innerField->uid,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);
    Fields::refreshFields();
    $entryType = EntryType::factory()->withField($contentBlockField)->create();
    $section = Section::factory()->withEntryTypes($entryType)->create(['type' => SectionType::Channel]);

    return [$section, $entryType, $contentBlockField];
}

function createContentBlockEntry(Section $section, EntryType $entryType, string $text): EntryElement
{
    $entry = Entry::factory()->forSection($section)->forEntryType($entryType)->createElement();
    $entry->setFieldValue('testBlock', ['fields' => ['blockText' => $text]]);
    Elements::saveElement($entry);

    return $entry;
}

it('loads the content block for a clone of an entry from a multi-result query', function () {
    [$section, $entryType] = createContentBlockSection();
    $entryIds = array_map(
        fn (string $text) => createContentBlockEntry($section, $entryType, $text)->id,
        ['first', 'second'],
    );

    [$entry] = EntryElement::find()->id($entryIds)->fixedOrder()->status(null)->all();
    $clone = clone $entry;

    $contentBlock = $clone->getFieldValue('testBlock');

    expect($contentBlock->id)->not->toBeNull()
        ->and($contentBlock->getFieldValue('blockText'))->toBe('first');
});

it('lazy-loads content blocks for a query result without querying their owners', function () {
    [$section, $entryType] = createContentBlockSection();
    $entryIds = array_map(
        fn (string $text) => createContentBlockEntry($section, $entryType, $text)->id,
        ['first', 'second', 'third'],
    );

    $entries = EntryElement::find()->id($entryIds)->fixedOrder()->status(null)->all();

    $entryQueries = 0;
    DB::listen(function (QueryExecuted $query) use (&$entryQueries) {
        if (preg_match('/from ["`]?(\w+_)?entries["`]?/i', $query->sql)) {
            $entryQueries++;
        }
    });

    foreach ($entries as $i => $entry) {
        $contentBlock = $entry->getFieldValue('testBlock');

        expect($contentBlock->getFieldValue('blockText'))->toBe(['first', 'second', 'third'][$i])
            ->and($contentBlock->getOwner())->toBe($entry)
            ->and($contentBlock->getPrimaryOwner())->toBe($entry);
    }

    expect($entryQueries)->toBe(0);
});

it('gives content blocks queried for several sites the owner from their own site', function () {
    $otherSite = Site::factory()->create();
    [$section, $entryType, $contentBlockField] = createContentBlockSection();
    SectionSiteSettings::factory()->create(['sectionId' => $section->id, 'siteId' => $otherSite->id]);
    $entry = createContentBlockEntry($section, $entryType, 'text');

    $owners = [
        EntryElement::find()->id($entry->id)->siteId(Sites::getPrimarySite()->id)->status(null)->one(),
        EntryElement::find()->id($entry->id)->siteId($otherSite->id)->status(null)->one(),
    ];

    $contentBlocks = ContentBlockElement::find()
        ->fieldId($contentBlockField->id)
        ->owner($owners)
        ->all();

    expect($contentBlocks)->toHaveCount(2);
    foreach ($contentBlocks as $contentBlock) {
        expect($contentBlock->getOwner())->toBe(collect($owners)->firstWhere('siteId', $contentBlock->siteId));
    }
});
