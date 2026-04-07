<?php

declare(strict_types=1);

use craft\base\ElementInterface;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Events\ApplyingDraft;
use CraftCms\Cms\Element\Events\CreatingDraft;
use CraftCms\Cms\Element\Events\DraftApplied;
use CraftCms\Cms\Element\Events\DraftCreated;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->drafts = app(Drafts::class);
});

it('can get editable drafts', function () {
    Entry::factory()->create();

    $element = EntryElement::findOne();

    $this->drafts->createDraft($element, User::findOne()->id);

    expect($this->drafts->getEditableDrafts($element))->toBeEmpty();

    actingAs(User::find()->one());

    expect($this->drafts->getEditableDrafts($element))->not()->toBeEmpty();
});

it('can create a draft', function () {
    actingAs(User::findOne());

    Event::fake([
        CreatingDraft::class,
        DraftCreated::class,
    ]);

    Event::listen(CreatingDraft::class, fn () => true);
    Event::listen(DraftCreated::class, fn () => true);

    Entry::factory()->create();
    $element = EntryElement::findOne();

    expect($draft = $this->drafts->createDraft(
        canonical: $element,
        name: 'My draft',
        notes: 'Some notes',
    ))->toBeInstanceOf(ElementInterface::class);

    expect($draft->draftName)->toBe('My draft');
    expect($draft->draftNotes)->toBe('Some notes');

    Event::assertDispatchedOnce(CreatingDraft::class);
    Event::assertDispatchedOnce(DraftCreated::class);
});

it('can generate unique draft names', function () {
    expect($this->drafts->generateDraftName(1))->toBe('Draft 1');

    DB::table(Table::DRAFTS)->insert([
        'canonicalId' => 1,
        'name' => 'Draft 1',
    ]);

    expect($this->drafts->generateDraftName(1))->toBe('Draft 2');
});

it('can save an element as draft', function () {
    Entry::factory()->create();
    $element = EntryElement::findOne();

    expect($element->getIsDraft())->toBeFalse();

    $this->drafts->saveElementAsDraft($element, User::find()->one()->id);

    expect($element->getIsDraft())->toBeTrue();
});

it('can apply a draft', function () {
    actingAs(User::findOne());

    Event::fake([
        ApplyingDraft::class,
        DraftApplied::class,
    ]);

    Event::listen(ApplyingDraft::class, fn () => true);
    Event::listen(DraftApplied::class, fn () => true);

    Entry::factory()->create();
    $element = EntryElement::findOne();

    $draft = $this->drafts->createDraft(
        canonical: $element,
        name: 'My draft',
        notes: 'Some notes',
    );

    expect(DB::table(Table::DRAFTS)->count())->toBe(1);

    $element = $this->drafts->applyDraft($draft);

    expect(DB::table(Table::DRAFTS)->count())->toBe(0);

    expect($element->revisionNotes)->toBe('Some notes');

    Event::assertDispatchedOnce(ApplyingDraft::class);
    Event::assertDispatchedOnce(DraftApplied::class);
});

it('can remove draft data from an element', function () {
    actingAs(User::findOne());

    Entry::factory()->create();
    $element = EntryElement::findOne();

    $draft = $this->drafts->createDraft(
        canonical: $element,
        name: 'My draft',
        notes: 'Some notes',
    );

    expect($draft->draftId)->not()->toBeNull();

    $this->drafts->removeDraftData($draft);

    expect($draft->draftId)->toBeNull();
});

it('can replace canonical elements with provisional drafts', function () {
    actingAs(User::findOne());

    Entry::factory()->count(2)->create();
    $entries = EntryElement::find()->orderBy('elements.id')->all();

    $provisionalDraft = $this->drafts->createDraft($entries[0], User::findOne()->id, provisional: true);

    $elementsWithDrafts = $this->drafts->withProvisionalDrafts($entries);

    expect($elementsWithDrafts[0]->id)->toBe($provisionalDraft->id)
        ->and($elementsWithDrafts[0]->getCanonicalId())->toBe($entries[0]->id)
        ->and($elementsWithDrafts[1]->id)->toBe($entries[1]->id);
});

it('can replace canonical elements with provisional drafts for a preview user from context', function () {
    $previewUser = User::findOne();

    Entry::factory()->create();
    $entry = EntryElement::findOne();

    $provisionalDraft = $this->drafts->createDraft($entry, $previewUser->id, provisional: true);

    Context::addHidden(Drafts::CONTEXT_PREVIEW_USER_ID, $previewUser->id);

    expect($this->drafts->withProvisionalDrafts([$entry])[0]->id)->toBe($provisionalDraft->id);
});

it('can load provisional changes onto canonical elements', function () {
    actingAs(User::findOne());

    $field = Field::factory()->create([
        'handle' => 'testField',
        'type' => PlainText::class,
    ]);

    $entryModel = Entry::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($field))
        ->create();

    $entry = entryQuery()->id($entryModel->id)->one();
    $entry->title = 'Canonical title';
    $entry->slug = 'canonical-title';
    $entry->setFieldValue('testField', 'canonical field value');
    Elements::saveElement($entry);

    $draft = $this->drafts->createDraft($entry, User::findOne()->id, provisional: true);
    $draft->title = 'Draft title';
    $draft->setDirtyAttributes(['title']);
    $draft->setFieldValue('testField', 'draft field value');
    $draft->setDirtyFields(['testField']);
    Elements::saveElement($draft);

    expect(DB::table(Table::CHANGEDATTRIBUTES)
        ->where('elementId', $draft->id)
        ->where('siteId', $draft->siteId)
        ->where('attribute', 'title')
        ->exists())->toBeTrue()
        ->and(DB::table(Table::CHANGEDFIELDS)
            ->where('elementId', $draft->id)
            ->where('siteId', $draft->siteId)
            ->where('fieldId', $field->id)
            ->exists())->toBeTrue();

    $entry = entryQuery()->id($entry->id)->one();
    $draft = entryQuery()->id($draft->id)->drafts()->provisionalDrafts()->one();

    expect($draft->getModifiedAttributes())->toContain('title')
        ->and($draft->getModifiedFields())->toContain('testField');

    $this->drafts->loadProvisionalChanges([$entry], User::findOne());

    expect($entry->hasProvisionalChanges)->toBeTrue()
        ->and($entry->title)->toBe('Draft title')
        ->and($entry->getFieldValue('testField'))->toBe('draft field value');
});

it('preserves matrix nested field values through draft apply', function () {
    actingAs(User::findOne());

    $innerField = Field::factory()->create([
        'name' => 'Inner Text',
        'handle' => 'innerText',
        'type' => PlainText::class,
    ]);

    $matrixEntryType = EntryType::factory()
        ->withField($innerField)
        ->create([
            'name' => 'Matrix Block',
            'handle' => 'matrixBlock',
            'hasTitleField' => true,
        ]);

    $matrixField = Field::factory()->create([
        'name' => 'Matrix Field',
        'handle' => 'matrixField',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$matrixEntryType->id]],
    ]);

    $entryModel = Entry::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($matrixField))
        ->create();

    $entry = entryQuery()->id($entryModel->id)->one();
    $blockUid = Str::uuid()->toString();

    $entry->setFieldValueFromRequest('matrixField', [
        'entries' => [
            "uid:$blockUid" => [
                'type' => $matrixEntryType->handle,
                'title' => 'Block 1',
                'enabled' => true,
                'fields' => [
                    'innerText' => 'Canonical matrix value',
                ],
            ],
        ],
        'sortOrder' => [$blockUid],
    ]);

    Elements::saveElement($entry);

    expect($entry->getFieldValue('matrixField')->status(null)->one()->getFieldValue('innerText'))
        ->toBe('Canonical matrix value');

    $draft = $this->drafts->createDraft($entry, User::findOne()->id, name: 'Matrix draft');

    expect($draft->siteSettingsId)->not->toBeNull();

    $canonicalBlock = $draft->getFieldValue('matrixField')->status(null)->one();

    $draft->setFieldValueFromRequest('matrixField', [
        'entries' => [
            "uid:$canonicalBlock->uid" => [
                'type' => $matrixEntryType->handle,
                'title' => 'Block 1',
                'enabled' => true,
                'fields' => [
                    'innerText' => 'Draft matrix value',
                ],
            ],
        ],
        'sortOrder' => [$canonicalBlock->uid],
    ]);

    $draftMatrixQuery = $draft->getFieldValue('matrixField');
    $draftMatrixEntry = $draftMatrixQuery->getResultOverride()[0];

    expect($draftMatrixQuery->getResultOverride())->not->toBeNull()
        ->and($draftMatrixEntry->id)->not->toBe($canonicalBlock->id)
        ->and($draftMatrixEntry->draftId)->not->toBeNull()
        ->and($draftMatrixEntry->getOwnerId())->toBe($draft->id)
        ->and($draftMatrixEntry->getPrimaryOwnerId())->toBe($draft->id)
        ->and($draftMatrixEntry->getFieldValue('innerText'))->toBe('Draft matrix value');

    Elements::saveElement($draft);

    $savedDraftQuery = $draft->getFieldValue('matrixField')->status(null);
    $savedDraftBlock = $savedDraftQuery->one();
    $draftOwnedBlockIds = DB::table(Table::ELEMENTS_OWNERS)
        ->where('ownerId', $draft->id)
        ->pluck('elementId')
        ->all();

    expect($draftOwnedBlockIds)->toContain($savedDraftBlock->id)
        ->and($draftOwnedBlockIds)->toContain($draftMatrixEntry->id)
        ->and($draftOwnedBlockIds)->not->toContain($canonicalBlock->id)
        ->and($savedDraftBlock->getOwnerId())->toBe($draft->id)
        ->and($savedDraftBlock->getPrimaryOwnerId())->toBe($draft->id);

    expect($savedDraftBlock->getFieldValue('innerText'))
        ->toBe('Draft matrix value');

    $applied = $this->drafts->applyDraft($draft);
    $appliedBlock = $applied->getFieldValue('matrixField')->status(null)->one();

    expect($appliedBlock->getFieldValue('innerText'))
        ->toBe('Draft matrix value');
});
