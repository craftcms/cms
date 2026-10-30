<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travel;

/**
 * @return list<string>
 */
function nestedEntryTitles(EntryElement $owner, string $fieldHandle): array
{
    return EntryElement::find()
        ->ownerId($owner->id)
        ->fieldId(Fields::getFieldByHandle($fieldHandle)->id)
        ->siteId(Sites::getPrimarySite()->id)
        ->revisions($owner->getIsRevision() ? null : false)
        ->drafts($owner->getIsDraft() ? null : false)
        ->status(null)
        ->orderBy('sortOrder')
        ->pluck('title')
        ->all();
}

function nestedEntry(EntryElement $owner, string $fieldHandle, int $index): EntryElement
{
    return EntryElement::find()
        ->ownerId($owner->id)
        ->fieldId(Fields::getFieldByHandle($fieldHandle)->id)
        ->siteId(Sites::getPrimarySite()->id)
        ->revisions($owner->getIsRevision() ? null : false)
        ->drafts($owner->getIsDraft() ? null : false)
        ->status(null)
        ->orderBy('sortOrder')
        ->all()[$index];
}

it('gives a nested entry added through a draft a complete first revision on a multi-site install', function () {
    actingAs(User::factory()->admin()->create());

    $secondSite = Site::factory()->create();
    Sites::refreshSites();

    $childEntryType = EntryTypeModel::factory()->create(['handle' => 'child', 'hasTitleField' => true]);
    $innerMatrixField = Field::factory()->create([
        'handle' => 'innerBlocks',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$childEntryType->id]],
    ]);
    Fields::refreshFields();

    $blockEntryType = EntryTypeModel::factory()
        ->withField($innerMatrixField)
        ->create(['handle' => 'block', 'hasTitleField' => true]);
    $outerMatrixField = Field::factory()->create([
        'handle' => 'blocks',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$blockEntryType->id], 'enableVersioning' => true],
    ]);
    Fields::refreshFields();

    $pageEntryType = EntryTypeModel::factory()->withField($outerMatrixField)->create(['hasTitleField' => true]);
    EntryTypes::refreshEntryTypes();

    $section = Section::factory()
        ->withEntryTypes($pageEntryType)
        ->withSites($secondSite)
        ->create([
            'type' => SectionType::Channel,
            'enableVersioning' => true,
            'propagationMethod' => PropagationMethod::All,
        ]);

    $page = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($pageEntryType)
        ->createElement(['title' => 'Page']);
    expect(Elements::saveElement($page))->toBeTrue();

    travel(1)->minutes();

    /** @var EntryElement $draft */
    $draft = app(Drafts::class)->createDraft($page, auth()->id());
    $draft->setFieldValue('blocks', [
        'new1' => [
            'type' => 'block',
            'title' => 'Block',
            'fields' => [
                'innerBlocks' => [
                    'new1' => ['type' => 'child', 'title' => 'Child'],
                ],
            ],
        ],
    ]);
    expect(Elements::saveElement($draft))->toBeTrue();

    $draftBlock = nestedEntry($draft, 'blocks', 0);
    expect(EntryElement::find()->revisionOf($draftBlock)->site('*')->status(null)->count())->toBe(0);

    $page = app(Drafts::class)->applyDraft($draft);
    $block = nestedEntry($page, 'blocks', 0);
    expect(nestedEntryTitles($block, 'innerBlocks'))->toBe(['Child']);

    $firstBlockRevision = EntryElement::find()
        ->revisionOf($block)
        ->siteId(Sites::getPrimarySite()->id)
        ->status(null)
        ->orderBy('elements.id')
        ->one();
    expect($firstBlockRevision)->not->toBeNull()
        ->and(nestedEntryTitles($firstBlockRevision, 'innerBlocks'))->toBe(['Child']);

    $pageRevision = EntryElement::find()
        ->revisionOf($page)
        ->siteId(Sites::getPrimarySite()->id)
        ->status(null)
        ->orderBy(['elements.id' => SORT_DESC])
        ->one();

    $page = app(Revisions::class)->revertToRevision($pageRevision, auth()->id());

    expect(nestedEntryTitles(nestedEntry($page, 'blocks', 0), 'innerBlocks'))->toBe(['Child']);
});
