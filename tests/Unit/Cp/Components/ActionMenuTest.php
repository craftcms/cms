<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\ActionMenu;

it('renders handle and description lines under the label', function () {
    $html = ActionMenu::make()->items([
        ['label' => 'News', 'handle' => 'news', 'description' => 'Timely <stories>'],
    ])->toHtml();

    expect($html)
        ->toContain('<span class="menu-item-description mt-2xs text-xs font-mono text-quiet">news</span><span class="menu-item-description mt-2xs text-xs text-quiet">Timely &lt;stories&gt;</span>')
        ->toContain('<span class="inline-flex flex-col items-start gap-2xs">News<span');
});

it('renders a bare label without secondary lines', function () {
    expect(ActionMenu::make()->items([['label' => 'News']])->toHtml())
        ->not->toContain('menu-item-description')
        ->not->toContain('inline-flex');
});

it('renders keywords for the search filter', function () {
    expect(ActionMenu::make()->items([['label' => 'News', 'keywords' => 'news story']])->toHtml())
        ->toContainTag('craft-action-item', ['data-keywords' => 'news story']);
});
