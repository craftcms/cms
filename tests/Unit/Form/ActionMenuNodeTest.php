<?php

declare(strict_types=1);

use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\ActionMenu;
use CraftCms\Cms\Form\Nodes\CopyAttribute;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Shared\Enums\Color;
use Symfony\Component\DomCrawler\Crawler;

/** @param  list<array<string, mixed>>  $items */
function actionMenuForm(array $items, ?CopyAttribute $chip = null): Form
{
    $nodes = [ActionMenu::make('field-actions:label:menu', $items)];

    if ($chip !== null) {
        $nodes[] = $chip;
    }

    return Form::make([
        Field::make('Label', Text::make('label'))->actions(...$nodes),
    ]);
}

it('converts menu-item configs into JSON-safe client descriptors', function () {
    $node = ActionMenu::make('menu', [
        [
            'icon' => 'gear',
            'label' => 'Field settings',
            'color' => Color::Red,
            'action' => ['type' => 'event', 'name' => 'craft:edit-field', 'detail' => ['fieldId' => 7]],
        ],
    ]);

    $items = $node->props()['items'];

    expect($items)->toHaveCount(1)
        ->and($items[0]['label'])->toBe('Field settings')
        ->and($items[0]['icon'])->toBe('gear')
        // Enums must be unwrapped — FormResolver::ensureJsonSafe() rejects objects.
        ->and($items[0]['iconColor'])->toBe(Color::Red->value)
        ->and($items[0]['action'])->toBe([
            'type' => 'event',
            'name' => 'craft:edit-field',
            'detail' => ['fieldId' => 7],
        ])
        // IDs are mt_rand()-generated; leaving them in props would make the
        // payload differ on every render of a refreshable form.
        ->and($items[0])->not->toHaveKey('id')
        ->and(json_encode($node->props()))->toBeString();
});

it('converts a string action into an http descriptor', function () {
    $items = ActionMenu::make('menu', [
        ['label' => 'Delete', 'action' => 'fields/delete', 'params' => ['id' => 3]],
    ])->props()['items'];

    expect($items[0]['action']['type'])->toBe('http')
        ->and($items[0]['action']['method'])->toBe('POST')
        ->and($items[0]['action']['url'])->toContain('fields/delete')
        ->and($items[0]['action']['body'])->toBe(['id' => 3]);
});

it('moves destructive items behind a separator and trims stray rules', function () {
    $items = ActionMenu::make('menu', [
        ['type' => 'hr'],
        ['label' => 'Settings'],
        ['label' => 'Remove', 'destructive' => true],
        ['type' => 'hr'],
    ])->props()['items'];

    expect(array_column($items, 'type'))->toBe(['button', 'hr', 'button'])
        ->and($items[0]['label'])->toBe('Settings')
        ->and($items[2]['label'])->toBe('Remove')
        ->and($items[2]['variant'])->toBe('danger');
});

it('resolves into the field’s actions slot as a control-less node', function () {
    $payload = app(FormResolver::class)->resolve(
        actionMenuForm([['label' => 'Field settings', 'icon' => 'gear']]),
        new FormContext,
    );
    $field = $payload->nodes[0];

    expect($field->props['hasActions'])->toBeTrue()
        ->and($field->children)->toHaveCount(1)
        ->and($field->children[0]->component)->toBe('craft:action-menu')
        ->and($field->children[0]->uid)->toBe('field-actions:label:menu')
        ->and($field->children[0]->control)->toBeNull();
});

it('rejects two action menus sharing a UID', function () {
    $form = Form::make([
        Field::make('One', Text::make('one'))->actions(ActionMenu::make('dupe', [['label' => 'A']])),
        Field::make('Two', Text::make('two'))->actions(ActionMenu::make('dupe', [['label' => 'B']])),
    ]);

    expect(fn () => app(FormResolver::class)->resolve($form, new FormContext))
        ->toThrow(InvalidArgumentException::class, 'Duplicate Node UID [dupe]');
});

it('renders a craft-action-menu with declarative action items in the HTML fallback', function () {
    $payload = app(FormResolver::class)->resolve(
        actionMenuForm(
            [['label' => 'Copy field handle', 'icon' => 'clipboard', 'action' => ['type' => 'clipboard', 'value' => 'body']]],
            CopyAttribute::make('field-actions:label:handle', 'body'),
        ),
        new FormContext,
    );

    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));
    $menu = $crawler->filter('craft-field craft-action-menu[data-form-node="field-actions:label:menu"]');
    $item = $menu->filter('craft-action-item');

    expect($menu->count())->toBe(1)
        ->and($menu->filter('[slot="invoker"]')->count())->toBe(1)
        ->and($item->count())->toBe(1)
        ->and($item->text())->toContain('Copy field handle')
        ->and($item->attr('icon'))->toBe('clipboard')
        ->and(json_decode($item->attr('action'), true))->toBe(['type' => 'clipboard', 'value' => 'body'])
        ->and($crawler->filter('craft-copy-attribute[value="body"]')->count())->toBe(1);
});
