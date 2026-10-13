<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\EntryTypeSelect;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());

    $model = EntryTypeModel::factory()->create([
        'name' => 'News',
        'handle' => 'news',
    ]);

    $this->entryTypes = app(EntryTypes::class);
    $this->entryTypes->refreshEntryTypes();
    $this->entryType = $this->entryTypes->getEntryTypeById($model->id);
});

it('offers every entry type by default', function () {
    $other = EntryTypeModel::factory()->create();
    $this->entryTypes->refreshEntryTypes();

    expect(EntryTypeSelect::make()->toHtml())
        ->toContainTag('craft-component-select', ['show-handles' => true])
        ->toContainTag('craft-action-item', ['data-id' => $this->entryType->id])
        ->toContainTag('craft-action-item', ['data-id' => $other->id]);
});

it('posts IDs without overrides', function () {
    expect(EntryTypeSelect::make()->name('entryTypes[]')->values([$this->entryType])->toHtml())
        ->toContainTag('input', ['name' => 'entryTypes[]', 'value' => $this->entryType->id]);
});

it('posts override configs when overrides are allowed', function () {
    $overridden = $this->entryTypes->getEntryType([
        'id' => $this->entryType->id,
        'name' => 'Story',
        'group' => 'Content',
    ]);

    $html = EntryTypeSelect::make()
        ->name('entryTypes[]')
        ->values([$overridden])
        ->allowOverrides()
        ->includeGroupInValues()
        ->toHtml();

    expect($html)->toContainTag('input', [
        'name' => 'entryTypes[]',
        'value' => sprintf('{"id":%s,"group":"Content","name":"Story"}', $this->entryType->id),
    ]);
});

it('links the Create button to a new entry type', function () {
    expect(EntryTypeSelect::make()->create()->toHtml())
        ->toContainTag('craft-component-select', ['create-action' => true])
        ->toContain('settings/entry-types/new');
});
