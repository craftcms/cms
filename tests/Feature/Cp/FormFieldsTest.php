<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;

use function Pest\Laravel\actingAs;

it('throws for an invalid site id in multi-site mode', function () {
    Site::factory()->create();
    Sites::refreshSites();

    expect(fn () => FormFields::fieldHtml('<input>', ['siteId' => -1]))
        ->toThrow(InvalidArgumentException::class, 'Invalid site ID: -1');
});

it('renders a field select listing every field with handles', function () {
    $model = Field::factory()->create();
    Fields::refreshFields();

    $html = FormFields::fieldSelectHtml(['id' => 'f', 'name' => 'fieldId', 'create' => true]);

    expect($html)
        ->toContainTag('craft-component-select', ['id' => 'f', 'name' => 'fieldId', 'show-handles' => true, 'create-action' => true])
        ->toContainTag('craft-action-item', ['data-id' => (string) $model->id]);
});

it('renders the attribute copy button for admins who show field handles', function () {
    $user = User::findOne();
    app(Users::class)->saveUserPreferences($user, ['showFieldHandles' => true]);
    actingAs($user);

    $html = FormFields::fieldHtml('<input>', [
        'id' => 'title',
        'label' => 'Title',
        'attribute' => 'title<b>',
        'showAttribute' => true,
    ]);

    expect($html)
        ->toContainTag('craft-copy-attribute', ['value' => 'title<b>', 'slot' => 'actions'])
        ->toContain('>title&lt;b&gt;</craft-copy-attribute>');
});
