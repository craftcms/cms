<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\MessageBag;

use function CraftCms\Cms\template;
use function Pest\Laravel\actingAs;

it('renders the override fields with ui() and wires them to their errors', function () {
    actingAs(User::findOne());

    $model = EntryTypeModel::factory()->create();
    $entryTypes = app(EntryTypes::class);
    $entryTypes->refreshEntryTypes();
    $original = $entryTypes->getEntryTypeById($model->id);
    $entryType = clone $original;
    $entryType->original = $original;
    $entryType->handle = 'customHandle';

    $html = template('settings/entry-types/_selection-settings', [
        'entryType' => $entryType,
        'errors' => new MessageBag(['handle' => ['That handle is taken.']]),
    ]);

    expect($html)
        ->toContainTag('craft-field', ['id' => 'name-field', 'data-error-key' => 'name'])
        ->toContainTag('input', ['id' => 'name', 'name' => 'name', 'placeholder' => $original->name])
        ->toContainTag('craft-field', ['id' => 'handle-field', 'data-error-key' => 'handle', 'has-errors' => true])
        ->toContainTag('input', ['id' => 'handle', 'value' => 'customHandle', 'maxlength' => '64'])
        ->toContainTag('textarea', ['id' => 'description', 'name' => 'description'])
        ->toContain('That handle is taken.');
});
