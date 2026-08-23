<?php

declare(strict_types=1);

use CraftCms\Cms\FieldLayout\Events\FieldLayoutActionMenuItemsResolving;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\TitleField;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\ActionMenu;
use CraftCms\Cms\Form\Nodes\CopyAttribute;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Users;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

function fieldActionNodes(
    \CraftCms\Cms\FieldLayout\LayoutElements\BaseField $field,
    ControlMode $mode = ControlMode::Editable,
): array {
    return $field->formNode(new FieldLayoutElementContext(null, new FormContext, $mode))->children();
}

function customTextField(): CustomField
{
    return new CustomField(new PlainText(['id' => 1, 'handle' => 'body', 'name' => 'Body']));
}

beforeEach(function () {
    $this->admin = User::findOne();
    actingAs($this->admin);
});

it('gives a native field a copy-attribute action menu', function () {
    $nodes = fieldActionNodes(new TitleField);

    expect($nodes)->toHaveCount(1)
        ->and($nodes[0])->toBeInstanceOf(ActionMenu::class)
        ->and($nodes[0]->uid())->toBe('field-actions:title:menu');

    $items = $nodes[0]->props()['items'];

    expect($items[0]['label'])->toBe('Copy attribute name')
        ->and($items[0]['action'])->toBe([
            'type' => 'event',
            'name' => 'craft:copy-text-prompt',
            'detail' => ['label' => 'Attribute Name', 'value' => 'title'],
        ]);
});

it('gives a custom field the Field settings item alongside Copy field handle', function () {
    $items = fieldActionNodes(customTextField())[0]->props()['items'];
    $labels = array_column($items, 'label');

    expect($labels)->toContain('Field settings')
        ->and($labels)->toContain('Copy field handle');

    $settings = collect($items)->firstWhere('label', 'Field settings');

    expect($settings['icon'])->toBe('gear')
        ->and($settings['action'])->toBe([
            'type' => 'event',
            'name' => 'craft:edit-field',
            'detail' => ['fieldId' => 1],
        ]);
});

it('shows no action menu to a non-admin', function () {
    $user = UserModel::factory()->createElement(['admin' => false]);
    actingAs($user);

    expect(fieldActionNodes(customTextField()))->toBe([]);
});

it('swaps Copy field handle for the inline chip when the preference is on', function () {
    expect(fieldActionNodes(customTextField()))
        ->each->not->toBeInstanceOf(CopyAttribute::class);

    app(Users::class)->saveUserPreferences($this->admin, ['showFieldHandles' => true]);
    actingAs($this->admin);

    $nodes = fieldActionNodes(customTextField());
    $chip = collect($nodes)->first(fn ($node) => $node instanceof CopyAttribute);
    $labels = array_column(collect($nodes)->first(fn ($n) => $n instanceof ActionMenu)->props()['items'], 'label');

    expect($chip)->not->toBeNull()
        ->and($chip->uid())->toBe('field-actions:fields.body:handle')
        ->and($chip->props()['value'])->toBe('body')
        ->and($labels)->not->toContain('Copy field handle');
});

it('lets a listener amend the items', function () {
    Event::listen(function (FieldLayoutActionMenuItemsResolving $event) {
        expect($event->element)->toBeNull()
            ->and($event->static)->toBeFalse()
            ->and($event->fieldLayoutComponent)->toBeInstanceOf(TitleField::class);

        $event->items[] = ['label' => 'From a plugin', 'icon' => 'gear'];
    });

    $labels = array_column(fieldActionNodes(new TitleField)[0]->props()['items'], 'label');

    expect($labels)->toContain('From a plugin');
});

it('reports a static form to listeners', function () {
    Event::listen(function (FieldLayoutActionMenuItemsResolving $event) {
        expect($event->static)->toBeTrue();
    });

    fieldActionNodes(new TitleField, ControlMode::ReadOnly);
});
