<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayout as FieldLayoutConfig;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;

function makeTitleFieldLayout(): FieldLayout
{
    $config = FieldLayoutConfig::make(EntryElement::class);
    $config->tab('Content', fn (FieldLayoutTab $tab) => $tab->add(new EntryTitleField(['required' => true])));

    return FieldLayout::factory()->create(['type' => EntryElement::class, 'config' => $config->getConfig()]);
}

function makeRequiredPlainTextFieldLayout(): FieldLayout
{
    $field = Field::factory()->create(['name' => 'My Required Text', 'handle' => 'myRequiredText', 'type' => PlainText::class]);

    Fields::refreshFields();

    $config = FieldLayoutConfig::make(EntryElement::class);
    $config->tab('Content', fn (FieldLayoutTab $tab) => $tab->add(
        new EntryTitleField(['required' => true]),
        CustomField::make($field->handle)->required(),
    ));

    return FieldLayout::factory()->create(['type' => EntryElement::class, 'config' => $config->getConfig()]);
}

beforeEach(function () {
    $this->import = app(Import::class);

    $this->typeA = EntryType::factory()
        ->withFieldLayout(makeTitleFieldLayout())
        ->create(['name' => 'Type A', 'handle' => 'typeA', 'hasTitleField' => true]);
    $this->typeB = EntryType::factory()
        ->withFieldLayout(makeTitleFieldLayout())
        ->create(['name' => 'Type B', 'handle' => 'typeB', 'hasTitleField' => true]);

    $this->section = Section::factory()->withEntryTypes($this->typeA, $this->typeB)->create(['minAuthors' => 0]);

    $this->sectionWithAuthors = Section::factory()->withEntryTypes($this->typeA)->create();

    $this->typeC = EntryType::factory()
        ->withFieldLayout(makeRequiredPlainTextFieldLayout())
        ->create(['name' => 'With Required Text', 'handle' => 'withRequiredText', 'hasTitleField' => true]);

    $this->importer = ElementImporter::create()
        ->className(EntryElement::class)
        ->site(Sites::getPrimarySite()->handle)
        ->fieldLayout(EntryTypes::getEntryTypeById($this->typeA->id)->getFieldLayout())
        ->transformer(null);
});

it('uses the entry type selected via the field layout provider, ignoring a typeId in the incoming data', function () {
    // typeId passed as an already-resolved int, to avoid the separate bug documented below.
    $this->import->importItem($this->importer, [
        'title' => 'imported entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->typeB->id,
    ]);

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry)->not->toBeNull()
        ->and($entry->getTypeId())->toBe($this->typeA->id);
});

it('fails and skips an entry imported enabled into a section requiring an author when no author is mapped', function () {
    $this->import->importItem($this->importer, [
        'title' => 'imported entry',
        'sectionId' => $this->sectionWithAuthors->handle,
    ]);

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry)->toBeNull();
});

it('succeeds importing a disabled entry into an author-requiring section without an author mapped', function () {
    $this->import->importItem($this->importer, [
        'title' => 'imported entry',
        'sectionId' => $this->sectionWithAuthors->handle,
        'enabled' => false,
    ]);

    $entry = EntryElement::find()->title('imported entry')->status(null)->one();

    expect($entry)->not->toBeNull();
});

it('succeeds importing an enabled entry into a section requiring an author when an author is mapped', function () {
    $author = User::factory()->create();
    // TODO (iwona): this is temporary, we need to handle items like that properly
    actingAs($author);

    $this->import->importItem($this->importer, [
        'title' => 'imported entry',
        'sectionId' => $this->sectionWithAuthors->handle,
        'authorIds' => [$author->id],
    ]);

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry)->not->toBeNull()
        ->and($entry->getAuthorIds())->toBe([$author->id]);
});

it('fails and skips an entry imported enabled with an unmapped required Plain Text field', function () {
    $section = Section::factory()->withEntryTypes($this->typeC)->create(['minAuthors' => 0]);
    $importer = $this->importer->fieldLayout(EntryTypes::getEntryTypeById($this->typeC->id)->getFieldLayout());

    $this->import->importItem($importer, [
        'title' => 'imported entry',
        'sectionId' => $section->handle,
    ]);

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry)->toBeNull();
});

it('succeeds importing a disabled entry with an unmapped required Plain Text field', function () {
    $section = Section::factory()->withEntryTypes($this->typeC)->create(['minAuthors' => 0]);
    $importer = $this->importer->fieldLayout(EntryTypes::getEntryTypeById($this->typeC->id)->getFieldLayout());

    $this->import->importItem($importer, [
        'title' => 'imported entry',
        'sectionId' => $section->handle,
        'enabled' => false,
    ]);

    $entry = EntryElement::find()->title('imported entry')->status(null)->one();

    expect($entry)->not->toBeNull();
});

it('succeeds importing an enabled entry with a mapped required Plain Text field', function () {
    $section = Section::factory()->withEntryTypes($this->typeC)->create(['minAuthors' => 0]);
    $importer = $this->importer->fieldLayout(EntryTypes::getEntryTypeById($this->typeC->id)->getFieldLayout());

    $this->import->importItem($importer, [
        'title' => 'imported entry',
        'sectionId' => $section->handle,
        'myRequiredText' => 'some value',
    ]);

    $entry = EntryElement::find()->title('imported entry')->status(null)->one();

    expect($entry)->not->toBeNull()
        ->and($entry->getFieldValue('myRequiredText'))->toBe('some value');
});
