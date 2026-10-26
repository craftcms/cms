<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\ImportHelper;
use CraftCms\Cms\Import\Jobs\Import as ImportJob;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\ImportFixtures;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Import\UserImporter;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    Bus::fake();

    $this->originalRoot = Aliases::get('@root');
    Aliases::set('@root', dirname(__DIR__, 3));

    $plainTextField = ImportFixtures::plainTextField('plainText', 'Plain Text');
    $this->blockEntryType = ImportFixtures::blockEntryType('blockEt', [$plainTextField], 'Block ET');
    ImportFixtures::matrixField('matrixA', [$this->blockEntryType], 'Matrix A');
    ImportFixtures::matrixField('matrixB', [$this->blockEntryType], 'Matrix B');

    $entryType = ImportFixtures::entryTypeWithTitle([
        CustomField::make('plainText'),
        CustomField::make('matrixA'),
        CustomField::make('matrixB'),
    ], ['name' => 'Fixture Type', 'handle' => 'fixtureType']);

    Section::factory()->withEntryTypes($entryType)->create(['handle' => 'fixtureSection', 'minAuthors' => 0]);

    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    $this->fieldLayout = EntryTypes::getEntryTypeByHandle('fixtureType')->getFieldLayout();

    $this->importer = new EntryImporter()
        ->site(Sites::getPrimarySite()->handle)
        ->section('fixtureSection')
        ->entryType('fixtureType');

    $this->colPath = fn (array $cols, string $handle) => implode('.', collect($cols)->firstWhere('handle', $handle)->prefixedHandleAsArray);

    $this->blockCols = function (string $matrixHandle) {
        $matrixField = Fields::getFieldByHandle($matrixHandle);
        $matrixCol = collect($this->importer->getDestinationCols())->firstWhere('handle', $matrixHandle);
        $provider = $matrixField->getFieldLayoutProviders()[0];

        return ImportHelper::getDestinationColsForFieldLayout($provider->getFieldLayout(), $matrixField, $provider, $matrixCol->prefixedHandle);
    };

    $this->runImport = function (string $importerClass, string $source, array $settings): void {
        $importPlan = new ImportPlanData([
            'name' => 'Renamed Handles',
            'handle' => 'renamedHandles',
            'uid' => 'plan-uid',
            'steps' => [
                new $importerClass([
                    'uid' => 'step-1',
                    'type' => $importerClass,
                    'source' => $source,
                    'settings' => ['site' => Sites::getPrimarySite()->handle, ...$settings],
                ]),
            ],
        ]);

        [$job] = new ImportJob($importPlan, 'step-1', $importerClass::resolvedSourcePath($source), 'run-id')->withFakeBatch();
        $job->handle();
    };

    $this->runEntryImport = fn (array $map) => ($this->runImport)(EntryImporter::class, 'tests/Fixtures/Import/entries-renamed-handles.json', [
        'section' => 'fixtureSection',
        'entryType' => 'fixtureType',
        'map' => $map,
    ]);

    $this->renameEntryTypeIn = function (string $matrixHandle, string $entryTypeHandle): void {
        $field = Field::query()->where('handle', $matrixHandle)->firstOrFail();
        $field->settings = [...$field->settings, 'entryTypes' => [['uid' => $this->blockEntryType->uid, 'handle' => $entryTypeHandle]]];
        $field->save();

        EntryTypes::refreshEntryTypes();
        Fields::refreshFields();
    };
});

afterEach(function () {
    Aliases::set('@root', $this->originalRoot);
});

it('imports into a custom field whose handle was overridden after it was mapped', function () {
    $cols = $this->importer->getDestinationCols();
    $map = [];
    Arr::set($map, 'title', 'heading');
    Arr::set($map, 'sectionId', 'section');
    Arr::set($map, 'typeId', 'entryType');
    Arr::set($map, ($this->colPath)($cols, 'plainText'), 'copy');

    $layoutElement = collect($this->fieldLayout->getCustomFieldElements())->first(fn (CustomField $element) => $element->attribute() === 'plainText');
    $layoutElement->handle = 'renamedText';
    Fields::saveLayout($this->fieldLayout);
    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    ($this->runEntryImport)($map);

    $entry = EntryElement::find()->title('imported entry')->status(null)->one();

    expect($entry->getFieldValue('renamedText'))->toBe('top text');
});

it('imports into an entry type whose handle was overridden differently in each matrix field after it was mapped', function () {
    $map = [];
    Arr::set($map, 'title', 'heading');
    Arr::set($map, 'sectionId', 'section');
    Arr::set($map, 'typeId', 'entryType');

    foreach (['matrixA', 'matrixB'] as $matrixHandle) {
        Arr::set($map, ($this->colPath)(($this->blockCols)($matrixHandle), 'plainText'), "$matrixHandle.copy");
    }

    ($this->renameEntryTypeIn)('matrixA', 'blockA');
    ($this->renameEntryTypeIn)('matrixB', 'blockB');

    ($this->runEntryImport)($map);

    $entry = EntryElement::find()->title('imported entry')->status(null)->one();
    $blockA = $entry->getFieldValue('matrixA')->status(null)->one();
    $blockB = $entry->getFieldValue('matrixB')->status(null)->one();

    expect($blockA?->getFieldValue('plainText'))->toBe('text a')
        ->and($blockB?->getFieldValue('plainText'))->toBe('text b');
});

it('imports into a user address custom field whose handle was overridden after it was mapped', function () {
    FieldLayout::factory()->withContentTab([CustomField::make('plainText')])->create(['type' => Address::class]);
    Fields::refreshFields();

    $cols = User::getDestinationColsForProperty(new UserImporter, 'addresses');
    $map = [];
    Arr::set($map, ($this->colPath)($cols, 'plainText'), 'addresses.note');

    $addressLayout = Fields::getLayoutByType(Address::class);
    $layoutElement = $addressLayout->getCustomFieldElements()[0];
    $layoutElement->handle = 'renamedNote';
    Fields::saveLayout($addressLayout);
    Fields::refreshFields();

    ($this->runImport)(UserImporter::class, 'tests/Fixtures/Import/users-renamed-handles.json', ['map' => $map]);

    $user = User::find()->username('importeduser')->status(null)->one();
    $address = Address::find()->ownerId($user->id)->one();

    expect($address?->getFieldValue('renamedNote'))->toBe('address text');
});
