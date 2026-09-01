<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\Matrix;

function createMatrixEmptyValueEntryType(): EntryTypeModel
{
    return EntryTypeModel::factory()
        ->withFieldLayout()
        ->create([
            'name' => 'Block',
            'handle' => 'block',
            'hasTitleField' => true,
        ]);
}

test('an empty request value clears all entries', function () {
    $entryType = createMatrixEmptyValueEntryType();

    $result = EntryModel::factory()
        ->withField('matrixField', Matrix::class, ['entryTypes' => [$entryType->id]], value: '')
        ->createElementWithFields(save: false);

    /** @var Matrix $field */
    $field = $result->element->getFieldLayout()->getFieldByHandle('matrixField');

    // An empty POST value arrives as null (Laravel's ConvertEmptyStringsToNull
    // middleware), and means every entry was removed.
    expect($field->normalizeValueFromRequest(null, $result->element)->getResultOverride())->toBe([])
        ->and($field->normalizeValueFromRequest('', $result->element)->getResultOverride())->toBe([])
        // Outside a request, null keeps lazy-loading the stored entries.
        ->and($field->normalizeValue(null, $result->element)->getResultOverride())->toBeNull();
});
