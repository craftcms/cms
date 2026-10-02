<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Import\AssetsFieldImportHandler;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Field\Assets as AssetsField;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Entries as EntriesField;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Events\RegisterFieldImportHandlers;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Import as ImportFacade;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\Import\TestFieldImportHandler;
use CraftCms\Cms\Tests\Support\ImportFixtures;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $textField = ImportFixtures::plainTextField('myPlainText', 'My Plain Text');

    Fields::refreshFields();

    $seed = ImportFixtures::seedEntry([CustomField::make($textField->handle)], ['name' => 'With Text', 'handle' => 'withText']);

    $this->section = $seed->section;
    $this->entryType = $seed->entryType;

    $this->importer = EntryImporter::create()
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null);

    TestFieldImportHandler::$calls = [];
});

it('lets a registered handler normalize a field’s imported value and act once the item is imported', function () {
    Event::listen(RegisterFieldImportHandlers::class, function (RegisterFieldImportHandlers $event) {
        $event->handlers[PlainText::class] = TestFieldImportHandler::class;
    });

    app(Import::class)->importItem($this->importer, [
        'title' => 'imported entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
        'myPlainText' => 'incoming',
    ]);

    expect(EntryElement::find()->title('imported entry')->one()->getFieldValue('myPlainText'))->toBe('normalized: incoming')
        ->and(TestFieldImportHandler::$calls)->toBe(['normalizeValue', 'afterItemImported']);
});

it('uses the handler registered for the nearest class in a field’s hierarchy', function () {
    Event::listen(RegisterFieldImportHandlers::class, function (RegisterFieldImportHandlers $event) {
        $event->handlers = [BaseRelationField::class => TestFieldImportHandler::class, ...$event->handlers];
    });

    expect(ImportFacade::getFieldImportHandlerFor(new AssetsField))->toBeInstanceOf(AssetsFieldImportHandler::class)
        ->and(ImportFacade::getFieldImportHandlerFor(new EntriesField))->toBeInstanceOf(TestFieldImportHandler::class)
        ->and(ImportFacade::getFieldImportHandlerFor(new PlainText))->toBeNull();
});

it('discards a handler’s after-item callbacks when the item fails to import', function () {
    Event::listen(RegisterFieldImportHandlers::class, function (RegisterFieldImportHandlers $event) {
        $event->handlers[PlainText::class] = TestFieldImportHandler::class;
    });

    expect(fn () => app(Import::class)->importItem($this->importer, [
        'title' => 'imported entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
        'postDate' => '2020-06-15 12:00:00',
        'expiryDate' => '2020-01-01 12:00:00',
        'myPlainText' => 'incoming',
    ]))->toThrow(InvalidElementException::class);

    app(Import::class)->importItem($this->importer, [
        'title' => 'other entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
    ]);

    expect(TestFieldImportHandler::$calls)->toBe(['normalizeValue']);
});
