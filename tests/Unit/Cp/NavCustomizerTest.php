<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Cp\Data\NavNode;
use CraftCms\Cms\Cp\NavCustomizer;

beforeEach(function () {
    Cms::config()->cpTrigger('admin');
});

/** Dashboard, then Content (Entries with two sources, Assets) and Administration (Utilities). */
function customizerTree(): array
{
    return [
        new NavItem()->id('nav-dashboard')->label('Dashboard')->href('/admin/dashboard'),
        new NavItem()->id('nav-group-content')->label('Content')->group(true)->subnav([
            new NavItem()->id('nav-entries')->label('Entries')->href('/admin/content/entries')->subnav([
                new NavItem()->id('nav-entries-blog')->label('Blog')->href('/admin/content/entries/blog'),
                new NavItem()->id('nav-entries-news')->label('News')->href('/admin/content/entries/news'),
            ]),
            new NavItem()->id('nav-assets')->label('Assets')->href('/admin/assets'),
        ]),
        new NavItem()->id('nav-group-administration')->label('Administration')->group(true)->subnav([
            new NavItem()->id('nav-utilities')->label('Utilities')->href('/admin/utilities'),
        ]),
    ];
}

/**
 * The rendered nav as nested labels.
 *
 * @param  NavItem[]  $items
 */
function navLabels(array $items): array
{
    return array_map(
        fn (NavItem $item) => is_array($item->subnav) && $item->subnav !== []
            ? [$item->label => navLabels($item->subnav)]
            : $item->label,
        $items,
    );
}

/**
 * The builder's tree as it posts it back.
 *
 * @param  NavNode[]  $tree
 */
function postedTree(array $tree): array
{
    return array_map(fn (NavNode $node) => $node->toArray(), $tree);
}

/** The editor tree, with `$edit` applied to it, as the builder would post it back. */
function editedTree(NavCustomizer $customizer, array $diff, Closure $edit): array
{
    $tree = postedTree($customizer->resolve(customizerTree(), $diff));

    return $edit($tree);
}

it('saves only what the builder changed, and renders it back', function () {
    $customizer = new NavCustomizer;

    $diff = $customizer->diff(customizerTree(), editedTree($customizer, [], function (array $tree) {
        [$dashboard, $content, $administration] = $tree;
        [$entries, $assets] = $content['children'];

        $entries['label'] = 'Articles';
        $entries['children'] = array_reverse($entries['children']);
        $administration['children'][] = $assets;
        $content['children'] = [$entries];
        $dashboard['hidden'] = true;

        return [$dashboard, $administration, $content];
    }));

    expect($diff)->toBe([
        'order' => [
            'root' => ['nav-dashboard', 'nav-group-administration', 'nav-group-content'],
            'nav-entries' => ['nav-entries-news', 'nav-entries-blog'],
        ],
        'items' => [
            'nav-dashboard' => ['hidden' => true],
            'nav-assets' => ['parent' => 'nav-group-administration'],
            'nav-entries' => ['label' => 'Articles'],
        ],
    ])->and(navLabels($customizer->apply(customizerTree(), $diff, null)))->toBe([
        ['Administration' => ['Utilities', 'Assets']],
        ['Content' => [['Articles' => ['News', 'Blog']]]],
    ]);
});

it('shows items added after the customization was saved, and skips ones since removed', function () {
    $customizer = new NavCustomizer;
    $diff = [
        'order' => ['nav-group-content' => ['nav-assets', 'nav-gone', 'nav-entries']],
        'items' => ['nav-gone' => ['label' => 'Gone'], 'nav-assets' => ['label' => 'Files']],
    ];

    $tree = customizerTree();
    $tree[1]->subnav = [...$tree[1]->subnav, new NavItem()->id('nav-commerce')->label('Commerce')->href('/admin/commerce')];

    expect(navLabels($customizer->apply($tree, $diff, null))[1])->toBe([
        'Content' => ['Files', ['Entries' => ['Blog', 'News']], 'Commerce'],
    ]);
});

it('adds sections and links, moves things out from beneath their item, and drops a section left empty', function () {
    $customizer = new NavCustomizer;

    $rendered = $customizer->apply(customizerTree(), [
        'order' => ['root' => ['custom-shop00', 'nav-entries-blog', 'nav-dashboard']],
        'items' => [
            'custom-shop00' => ['type' => 'section', 'label' => 'Shop'],
            'custom-docs00' => ['type' => 'link', 'label' => 'Docs', 'url' => 'https://docs.example.com', 'parent' => 'custom-shop00', 'newWindow' => true],
            'nav-entries-blog' => ['parent' => 'root', 'label' => 'Blog posts'],
            'nav-utilities' => ['hidden' => true],
        ],
    ], null);

    expect(navLabels($rendered))->toBe([
        ['Shop' => ['Docs']],
        'Blog posts',
        'Dashboard',
        ['Content' => [['Entries' => ['News']], 'Assets']],
    ])->and($rendered[1]->href)->toBe('/admin/content/entries/blog')
        ->and($rendered[0]->subnav[0]->linkAttributes)->toBe(['target' => '_blank', 'rel' => 'noopener']);
});

it('keeps sections out of sections, and what’s beneath an item out of other items', function () {
    $customizer = new NavCustomizer;

    $diff = $customizer->diff(customizerTree(), editedTree($customizer, [], function (array $tree) {
        [$dashboard, $content, $administration] = $tree;
        $blog = array_shift($content['children'][0]['children']);
        $content['children'][1]['children'][] = $blog;
        $content['children'][] = $administration;

        return [$dashboard, $content];
    }));

    expect($diff['items'] ?? [])->toBe([])
        ->and(navLabels($customizer->apply(customizerTree(), $diff, null)))->toBe(navLabels(customizerTree()));
});

it('customizes what’s under a heading in a subnav, and moves it out, but not the heading', function () {
    $tree = fn () => [
        new NavItem()->id('nav-settings')->label('Settings')->href('/admin/settings')->subnav([
            new NavItem()->id('nav-settings-system')->label('System')->group(true)->subnav([
                new NavItem()->id('nav-settings-general')->label('General')->href('/admin/settings/general'),
                new NavItem()->id('nav-settings-sites')->label('Sites')->href('/admin/settings/sites'),
            ]),
            new NavItem()->id('nav-settings-extend')->label('Extend')->group(true)->subnav([
                new NavItem()->id('nav-settings-plugins')->label('Plugins')->href('/admin/settings/plugins'),
            ]),
        ]),
    ];
    $customizer = new NavCustomizer;

    $edited = postedTree($customizer->resolve($tree(), []));
    [$system, $extend] = $edited[0]['children'];
    [$general, $sites] = $system['children'];
    $general['hidden'] = true;
    $sites['label'] = 'Websites';
    $system['children'] = [$sites];
    $edited[0]['children'] = [$system];
    $edited[] = [...$general, 'hidden' => false];
    $edited[] = $extend;

    expect(navLabels($customizer->apply($tree(), $customizer->diff($tree(), $edited), null)))->toBe([
        ['Settings' => [['System' => ['Websites']], ['Extend' => ['Plugins']]]],
        'General',
    ]);
});

it('moves something from beneath an item into a section, children and all, and back again', function () {
    $tree = fn () => [
        new NavItem()->id('nav-users')->label('Users')->href('/admin/users')->subnav([
            new NavItem()->id('nav-users-admins')->label('Admins')->href('/admin/users/admins')->subnav([
                new NavItem()->id('nav-users-admins-active')->label('Active')->href('/admin/users/admins/active'),
            ]),
        ]),
        new NavItem()->id('nav-group-administration')->label('Administration')->group(true)->subnav([
            new NavItem()->id('nav-utilities')->label('Utilities')->href('/admin/utilities'),
        ]),
    ];
    $customizer = new NavCustomizer;

    $edited = postedTree($customizer->resolve($tree(), []));
    $admins = array_pop($edited[0]['children']);
    $edited[1]['children'][] = $admins;
    $diff = $customizer->diff($tree(), $edited);

    expect($diff['items'])->toBe(['nav-users-admins' => ['parent' => 'nav-group-administration']])
        ->and(navLabels($customizer->apply($tree(), $diff, null)))->toBe([
            'Users',
            ['Administration' => ['Utilities', ['Admins' => ['Active']]]],
        ]);

    $edited = postedTree($customizer->resolve($tree(), $diff));
    $admins = array_pop($edited[1]['children']);
    $edited[0]['children'][] = $admins;

    expect($customizer->diff($tree(), $edited))->toBe([]);
});

it('drops links to anything but control panel paths and web, mail and phone URLs, however deep they’re posted', function () {
    $customizer = new NavCustomizer;
    $edited = postedTree($customizer->resolve(customizerTree(), []));
    $edited[1]['children'][0]['children'][0]['children'] = [
        ['id' => 'custom-bad000', 'type' => 'link', 'label' => 'Bad', 'url' => 'javascript:alert(1)'],
        ['id' => 'custom-good00', 'type' => 'link', 'label' => 'Good', 'url' => 'https://docs.example.com'],
    ];

    expect($customizer->diff(customizerTree(), $edited)['items'])
        ->toHaveKey('custom-good00')
        ->not->toHaveKey('custom-bad000');
});

it('drops created items whose ids couldn’t be project config keys', function () {
    $customizer = new NavCustomizer;
    $edited = [
        ...postedTree($customizer->resolve(customizerTree(), [])),
        ['id' => 'custom-shop00', 'type' => 'section', 'label' => 'Shop', 'children' => []],
        ['id' => 'custom-a.b', 'type' => 'section', 'label' => 'Dotted', 'children' => []],
    ];

    expect(array_keys($customizer->diff(customizerTree(), $edited)['items']))->toBe(['custom-shop00']);
});
