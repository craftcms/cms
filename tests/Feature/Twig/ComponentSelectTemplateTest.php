<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\TemplateMode;

use function CraftCms\Cms\renderString;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());

    $model = EntryTypeModel::factory()->create();

    $entryTypes = app(EntryTypes::class);
    $entryTypes->refreshEntryTypes();
    $this->entryType = $entryTypes->getEntryTypeById($model->id);
});

it('renders a component select from the legacy componentSelect variables', function () {
    $html = renderString(
        "{{ craft.cp.componentSelect({id: 'types', name: 'types[]', options: [entryType], values: [entryType], sortable: false})|raw }}",
        ['entryType' => $this->entryType],
        TemplateMode::Cp,
    );

    expect($html)
        ->toContainTag('craft-component-select', ['id' => 'types', 'name' => 'types[]', 'sortable' => 'false'])
        ->toContainTag('craft-chip', ['data-id' => $this->entryType->id])
        ->toContainTag('craft-action-item', ['data-id' => $this->entryType->id, 'hidden' => true]);
});

it('renders an entry type select from the legacy entryTypeSelect variables', function () {
    $html = renderString(
        "{{ craft.cp.entryTypeSelect({name: 'entryTypes[]', values: [entryType], allowOverrides: true, create: true})|raw }}",
        ['entryType' => $this->entryType],
        TemplateMode::Cp,
    );

    expect($html)
        ->toContainTag('craft-component-select', ['show-handles' => true, 'show-description' => true, 'create-action' => true])
        ->toContainTag('input', ['name' => 'entryTypes[]', 'value' => sprintf('{"id":%s}', $this->entryType->id)]);
});
