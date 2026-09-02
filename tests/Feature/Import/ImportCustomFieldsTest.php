<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Assets as AssetsField;
use CraftCms\Cms\Field\ButtonGroup;
use CraftCms\Cms\Field\Checkboxes;
use CraftCms\Cms\Field\Color;
use CraftCms\Cms\Field\Country as CountryField;
use CraftCms\Cms\Field\Date as DateField;
use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\Email;
use CraftCms\Cms\Field\Entries as EntriesField;
use CraftCms\Cms\Field\Icon;
use CraftCms\Cms\Field\Json as JsonField;
use CraftCms\Cms\Field\Lightswitch;
use CraftCms\Cms\Field\Link as LinkField;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\Money as MoneyField;
use CraftCms\Cms\Field\MultiSelect;
use CraftCms\Cms\Field\Number;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\RadioButtons;
use CraftCms\Cms\Field\Range;
use CraftCms\Cms\Field\Table as TableField;
use CraftCms\Cms\Field\Time as TimeField;
use CraftCms\Cms\Field\Users as UsersField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;

beforeEach(function () {
    $this->import = app(Import::class);

    $options = [
        ['label' => 'Option A', 'value' => 'optionA', 'default' => false],
        ['label' => 'Option B', 'value' => 'optionB', 'default' => false],
        ['label' => 'Option C', 'value' => 'optionC', 'default' => false],
    ];

    $allFields = [
        Field::factory()->create(['name' => 'My Plain Text', 'handle' => 'myPlainText', 'type' => PlainText::class]),
        Field::factory()->create(['name' => 'My Email', 'handle' => 'myEmail', 'type' => Email::class]),
        Field::factory()->create(['name' => 'My Number', 'handle' => 'myNumber', 'type' => Number::class]),
        Field::factory()->create(['name' => 'My Color', 'handle' => 'myColor', 'type' => Color::class]),
        Field::factory()->create(['name' => 'My Country', 'handle' => 'myCountry', 'type' => CountryField::class]),
        Field::factory()->create(['name' => 'My Lightswitch', 'handle' => 'myLightswitch', 'type' => Lightswitch::class]),
        Field::factory()->create(['name' => 'My Range', 'handle' => 'myRange', 'type' => Range::class]),
        Field::factory()->create(['name' => 'My Json', 'handle' => 'myJson', 'type' => JsonField::class]),
        Field::factory()->create(['name' => 'My Date', 'handle' => 'myDate', 'type' => DateField::class]),
        Field::factory()->create(['name' => 'My Time', 'handle' => 'myTime', 'type' => TimeField::class]),
        Field::factory()->create(['name' => 'My Money', 'handle' => 'myMoney', 'type' => MoneyField::class, 'settings' => ['currency' => 'USD']]),
        Field::factory()->create(['name' => 'My Icon', 'handle' => 'myIcon', 'type' => Icon::class]),
        Field::factory()->create(['name' => 'My Link', 'handle' => 'myLink', 'type' => LinkField::class]),
        Field::factory()->create(['name' => 'My Dropdown', 'handle' => 'myDropdown', 'type' => Dropdown::class, 'settings' => ['options' => $options]]),
        Field::factory()->create(['name' => 'My Radio Buttons', 'handle' => 'myRadioButtons', 'type' => RadioButtons::class, 'settings' => ['options' => $options]]),
        Field::factory()->create(['name' => 'My Button Group', 'handle' => 'myButtonGroup', 'type' => ButtonGroup::class, 'settings' => ['options' => $options]]),
        Field::factory()->create(['name' => 'My Checkboxes', 'handle' => 'myCheckboxes', 'type' => Checkboxes::class, 'settings' => ['options' => $options]]),
        Field::factory()->create(['name' => 'My Multi-select', 'handle' => 'myMultiSelect', 'type' => MultiSelect::class, 'settings' => ['options' => $options]]),
        Field::factory()->create([
            'name' => 'My Table',
            'handle' => 'myTable',
            'type' => TableField::class,
            'settings' => [
                'columns' => [
                    'col1' => ['heading' => 'Column 1', 'handle' => 'col1', 'type' => 'singleline'],
                    'col2' => ['heading' => 'Column 2', 'handle' => 'col2', 'type' => 'number'],
                ],
            ],
        ]),
        Field::factory()->create(['name' => 'My Entries', 'handle' => 'myEntries', 'type' => EntriesField::class]),
        Field::factory()->create(['name' => 'My Users', 'handle' => 'myUsers', 'type' => UsersField::class]),
        Field::factory()->create(['name' => 'My Assets', 'handle' => 'myAssets', 'type' => AssetsField::class]),
    ];

    Fields::refreshFields();

    $layoutElements[] = new EntryTitleField(['uid' => Str::uuid()->toString(), 'required' => true]);
    foreach ($allFields as $field) {
        $layoutElements[] = CustomField::make($field->handle);
    }

    $fieldLayout = FieldLayout::factory()->withContentTab($layoutElements)->create();

    $entryType = EntryType::factory()
        ->withFieldLayout($fieldLayout)
        ->create(['name' => 'With All Fields', 'handle' => 'withAllFields', 'hasTitleField' => true]);

    $section = Section::factory()->withEntryTypes($entryType)->create(['minAuthors' => 0]);

    $seedResult = Entry::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->withFieldLayout($fieldLayout)
        ->createElementWithFields(['title' => 'seed entry', 'slug' => 'seed-entry']);

    $this->section = $seedResult->element->getSection();
    $this->entryType = $seedResult->element->getType();

    $this->importer = ElementImporter::create()
        ->className(EntryElement::class)
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null);

    $this->entryData = fn (array $fieldValues) => array_merge([
        'title' => 'imported entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
    ], $fieldValues);
});

it('imports a plain text field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myPlainText' => 'hello world']));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myPlainText'))->toBe('hello world');
});

it('imports an email field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myEmail' => 'test@example.com']));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myEmail'))->toBe('test@example.com');
});

it('imports a number field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myNumber' => 42]));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myNumber'))->toEqual(42);
});

it('imports a color field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myColor' => '#ff0000']));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect((string) $entry->getFieldValue('myColor'))->toBe('#ff0000');
});

it('imports a country field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myCountry' => 'US']));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myCountry')->getCountryCode())->toBe('US');
});

it('imports a lightswitch field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myLightswitch' => true]));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myLightswitch'))->toBeTrue();
});

it('imports a range field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myRange' => 50]));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myRange'))->toEqual(50);
});

it('imports a json field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myJson' => ['foo' => 'bar']]));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myJson')['foo'])->toBe('bar');
});

it('imports a date field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myDate' => '2025-06-15 12:00:00']));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myDate')->format('Y-m-d'))->toBe('2025-06-15');
});

it('imports a time field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myTime' => '14:30']));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myTime')->format('H:i'))->toBe('14:30');
});

it('imports a money field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myMoney' => ['value' => '10.00', 'currency' => 'USD']]));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myMoney')->getAmount())->toBe('1000');
});

it('imports an icon field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myIcon' => 'anchor']));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myIcon')->name)->toBe('anchor');
});

it('imports a link field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myLink' => 'https://craftcms.com']));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myLink')->getUrl())->toBe('https://craftcms.com');
});

it('imports a dropdown field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myDropdown' => 'optionB']));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect((string) $entry->getFieldValue('myDropdown'))->toBe('optionB');
});

it('imports a radio buttons field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myRadioButtons' => 'optionB']));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect((string) $entry->getFieldValue('myRadioButtons'))->toBe('optionB');
});

it('imports a button group field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myButtonGroup' => 'optionB']));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect((string) $entry->getFieldValue('myButtonGroup'))->toBe('optionB');
});

it('imports checkboxes field values', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myCheckboxes' => ['optionA', 'optionC']]));
    $entry = EntryElement::find()->title('imported entry')->one();
    $value = $entry->getFieldValue('myCheckboxes');
    expect($value->contains('optionA'))->toBeTrue()
        ->and($value->contains('optionC'))->toBeTrue();
});

it('imports a multi-select field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myMultiSelect' => ['optionA', 'optionC']]));
    $entry = EntryElement::find()->title('imported entry')->one();
    $value = $entry->getFieldValue('myMultiSelect');
    expect($value->contains('optionA'))->toBeTrue()
        ->and($value->contains('optionC'))->toBeTrue();
});

it('imports a table field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        'myTable' => [['col1' => 'text value', 'col2' => 42]],
    ]));
    $entry = EntryElement::find()->title('imported entry')->one();
    $rows = $entry->getFieldValue('myTable');
    expect($rows[0]['col1'])->toBe('text value')
        ->and($rows[0]['col2'])->toEqual(42);
});
