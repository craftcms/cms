<?php

declare(strict_types=1);

use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\Nodes\Heading;
use CraftCms\Cms\Ui\Ui;

/** A field bound to `$path`, which is also how it's addressed for insertion. */
function insertionField(string $path): Field
{
    return Field::make()->label($path)->control(Text::make($path));
}

/**
 * Each node's identity, the same way `InsertsNodes` addresses it.
 *
 * @param  list<Node>  $nodes
 * @return list<string|null>
 */
function insertionOrder(array $nodes): array
{
    return array_map(function (Node $node): ?string {
        if ($node->uid() !== null) {
            return $node->uid();
        }

        $path = $node->getControl()?->path();

        return is_array($path) ? implode('.', $path) : $path;
    }, $nodes);
}

it('prepends nodes in the order given', function () {
    $ui = Ui::make([insertionField('title')])
        ->prepend(insertionField('first'), insertionField('second'));

    expect(insertionOrder($ui->nodes()))->toBe(['first', 'second', 'title']);
});

it('inserts at an index', function () {
    $ui = Ui::make([insertionField('a'), insertionField('c')])
        ->insertAt(1, insertionField('b'));

    expect(insertionOrder($ui->nodes()))->toBe(['a', 'b', 'c']);
});

it('counts a negative index from the end, like array_splice', function () {
    $ui = Ui::make([insertionField('a'), insertionField('c')])
        ->insertAt(-1, insertionField('b'));

    expect(insertionOrder($ui->nodes()))->toBe(['a', 'b', 'c']);
});

it('clamps an index past the end rather than throwing', function () {
    $ui = Ui::make([insertionField('a')])
        ->insertAt(PHP_INT_MAX, insertionField('b'));

    expect(insertionOrder($ui->nodes()))->toBe(['a', 'b']);
});

it('inserts before and after a field, addressed by its control path', function () {
    $ui = Ui::make([insertionField('title'), insertionField('slug')])
        ->insertBefore('slug', insertionField('before'))
        ->insertAfter('slug', insertionField('after'));

    expect(insertionOrder($ui->nodes()))
        ->toBe(['title', 'before', 'slug', 'after']);
});

it('addresses a pathless node by its uid', function () {
    $ui = Ui::make([Heading::make('notes', 'Notes')])
        ->insertBefore('notes', insertionField('above'));

    expect(insertionOrder($ui->nodes()))->toBe(['above', 'notes']);
});

it('matches a control path authored as an array', function () {
    $field = Field::make()->control(Text::make(['settings', 'nested']));
    $ui = Ui::make([$field])
        ->insertAfter('settings.nested', insertionField('after'));

    expect(insertionOrder($ui->nodes()))->toBe(['settings.nested', 'after']);
});

it('reaches a field nested inside a tab, landing it as a sibling', function () {
    $ui = Ui::make()
        ->addTab('Content', [insertionField('title'), insertionField('body')])
        ->insertAfter('title', insertionField('subtitle'));

    $tab = $ui->nodes()[0];

    expect(insertionOrder($ui->nodes()))->toBe(['content'])
        ->and(insertionOrder($tab->children()))
        ->toBe(['title', 'subtitle', 'body']);
});

it('reaches a field nested two containers deep', function () {
    $group = Group::make('meta', [insertionField('slug')]);
    $ui = Ui::make()
        ->addTab('Content', [$group])
        ->insertBefore('slug', insertionField('above'));

    expect(insertionOrder($group->children()))->toBe(['above', 'slug']);
});

it('throws when nothing matches', function () {
    Ui::make([insertionField('title')])
        ->insertBefore('nope', insertionField('x'));
})->throws(InvalidArgumentException::class, 'No UI node matches [nope].');

it('is a no-op when given no nodes', function () {
    $ui = Ui::make([insertionField('title')]);

    expect(insertionOrder($ui->prepend()->insertAt(0)->nodes()))->toBe(['title'])
        // An unmatched target can't throw when there is nothing to place.
        ->and(insertionOrder($ui->insertBefore('nope')->nodes()))->toBe(['title']);
});
