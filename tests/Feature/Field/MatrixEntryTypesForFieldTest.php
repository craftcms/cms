<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\Events\EntryTypesForFieldResolving;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Support\Json;
use Illuminate\Support\Facades\Event;

/**
 * @return array{0: Matrix, 1: Entry, 2: EntryTypeModel, 3: EntryTypeModel}
 */
function createMatrixEntryTypesForFieldSetup(string $viewMode): array
{
    $allowedEntryType = EntryTypeModel::factory()->withFieldLayout()->create(['name' => 'Allowed', 'handle' => 'allowed']);
    $excludedEntryType = EntryTypeModel::factory()->withFieldLayout()->create(['name' => 'Excluded', 'handle' => 'excluded']);

    $result = EntryModel::factory()
        ->withField('matrixField', Matrix::class, [
            'entryTypes' => [$allowedEntryType->id, $excludedEntryType->id],
            'viewMode' => $viewMode,
        ])
        ->createElementWithFields();

    /** @var Matrix $field */
    $field = $result->element->getFieldLayout()->getFieldByHandle('matrixField');

    return [$field, $result->element, $allowedEntryType, $excludedEntryType];
}

/**
 * @return array<string, mixed>
 */
function nestedElementManagerSettings(string $html): array
{
    preg_match('/<craft-nested-element-manager[^>]*\ssettings="([^"]*)"/', $html, $matches);

    return Json::decode(html_entity_decode($matches[1] ?? '{}'));
}

dataset('nested element manager view modes', [
    'cards' => Matrix::VIEW_MODE_CARDS,
    'cards grid' => Matrix::VIEW_MODE_CARDS_GRID,
    'index' => Matrix::VIEW_MODE_INDEX,
]);

test('nested element manager view modes use entry types defined by listeners', function (string $viewMode) {
    [$field, $owner, $allowedEntryType, $excludedEntryType] = createMatrixEntryTypesForFieldSetup($viewMode);

    $existingEntry = new Entry([
        'fieldId' => $field->id,
        'typeId' => $excludedEntryType->id,
        'siteId' => $owner->siteId,
        'title' => 'Existing excluded entry',
    ]);

    $value = Entry::find();
    $value->setResultOverride([$existingEntry]);

    $eventCount = 0;
    Event::listen(function (EntryTypesForFieldResolving $event) use (&$eventCount, $owner, $existingEntry, $allowedEntryType): void {
        $eventCount++;
        expect($event->element)->toBe($owner)
            ->and($event->value)->toBe([$existingEntry]);
        $event->entryTypes = array_values(array_filter(
            $event->entryTypes,
            fn ($entryType) => $entryType->id === $allowedEntryType->id,
        ));
    });

    $settings = nestedElementManagerSettings($field->getInlineInputHtml($value, $owner));

    expect($eventCount)->toBe(1)
        ->and($settings['pasteableData']['values'])->toBe([$allowedEntryType->id])
        ->and(array_column(array_column($settings['createAttributes'], 'attributes'), 'typeId'))->toBe([$allowedEntryType->id]);
})->with('nested element manager view modes');

test('nested element manager view modes use all entry types without listeners', function (string $viewMode) {
    [$field, $owner, $allowedEntryType, $excludedEntryType] = createMatrixEntryTypesForFieldSetup($viewMode);

    $value = Entry::find();
    $value->setResultOverride([]);

    $settings = nestedElementManagerSettings($field->getInlineInputHtml($value, $owner));

    expect($settings['pasteableData']['values'])->toBe([$allowedEntryType->id, $excludedEntryType->id])
        ->and(array_column(array_column($settings['createAttributes'], 'attributes'), 'typeId'))->toBe([$allowedEntryType->id, $excludedEntryType->id]);
})->with('nested element manager view modes');
