<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Elements\ContentBlock as ContentBlockElement;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Section\Models\Section as SectionModel;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
});

/**
 * Reproduction test for https://github.com/craftcms/cms/issues/19277.
 *
 * Applying a draft whose owner entry has a nested ContentBlock element threw an integrity error
 * (duplicate key on the sites table) if the canonical entry gained a new site while the draft's own
 * copy of the nested block was already forked off (edited) for the sites that existed at that time.
 */
it('applies a draft after the canonical entry gains a new site for a nested content block', function () {
    $innerField = Field::factory()->create([
        'name' => 'Block Text',
        'handle' => 'blockText',
        'type' => PlainText::class,
    ]);

    Fields::invalidateCaches();
    Fields::refreshFields();

    $layoutUid = Str::uuid()->toString();
    $contentBlockSettings = [
        'fieldLayouts' => [
            $layoutUid => [
                'tabs' => [
                    [
                        'uid' => Str::uuid()->toString(),
                        'name' => 'Content',
                        'elements' => [
                            [
                                'uid' => Str::uuid()->toString(),
                                'type' => CustomField::class,
                                'fieldUid' => $innerField->uid,
                                'required' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $entryType = EntryTypeModel::factory()->withFieldLayout()->create(['hasTitleField' => true]);
    $section = SectionModel::factory()->withEntryTypes($entryType)->create();

    $result = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->withField('testBlock', ContentBlock::class, $contentBlockSettings, value: ['fields' => ['blockText' => 'original']])
        ->createElementWithFields();

    Sections::refreshSections();

    $primarySiteId = $result->element->siteId;
    $entry = EntryElement::find()->id($result->element->id)->one();

    // Create a draft, and edit the draft's content block, forcing NestedElementManager/ContentBlock
    // to fork off a genuinely separate, draft-owned copy of the nested block for the sites the draft
    // currently supports (the primary site only).
    /** @var EntryElement $draft */
    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), 'Test Draft');
    $draft->setFieldValue('testBlock', ['fields' => ['blockText' => 'changed by draft']]);
    expect(app(Elements::class)->saveElement($draft))->toBeTrue();

    // Independently of the draft, add a second site to the section, and resave the canonical entry
    // so it (and its ContentBlock, via propagationMethod: All) propagates to that new site.
    $secondSite = Site::factory()->create();
    Sites::refreshSites();
    SectionSiteSettings::factory()->create([
        'sectionId' => $section->id,
        'siteId' => $secondSite->id,
        'hasUrls' => true,
        'dateCreated' => $section->dateCreated,
        'dateUpdated' => $section->dateUpdated,
    ]);
    Sections::refreshSections();

    $canonicalEntry = EntryElement::find()->id($entry->id)->siteId($primarySiteId)->status(null)->one();
    expect($canonicalEntry)->not->toBeNull();
    expect(app(Elements::class)->saveElement($canonicalEntry))->toBeTrue();

    // Sanity check: the canonical's nested block now has a row for the new site...
    $canonicalBlockForNewSite = ContentBlockElement::find()
        ->fieldId(Fields::getFieldByHandle('testBlock')->id)
        ->ownerId($entry->id)
        ->siteId($secondSite->id)
        ->status(null)
        ->one();
    expect($canonicalBlockForNewSite)->not->toBeNull();

    // ...while the draft's own (forked) copy of the block never caught up with the new site.
    $draftBlockForNewSite = ContentBlockElement::find()
        ->fieldId(Fields::getFieldByHandle('testBlock')->id)
        ->ownerId($draft->id)
        ->siteId($secondSite->id)
        ->drafts(null)
        ->status(null)
        ->one();
    expect($draftBlockForNewSite)->toBeNull();

    // Applying the draft must not throw an integrity error.
    app(Drafts::class)->applyDraft($draft);
});
