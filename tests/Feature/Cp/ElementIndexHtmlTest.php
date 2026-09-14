<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Html\ElementIndexHtml;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Middleware\RequireCpRequest;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());

    app(RequireCpRequest::class)->registerCpTemplateHooks();
});

it('renders an element index shell with toolbar and elements container', function () {
    $html = app(ElementIndexHtml::class)->html(Entry::class, [
        'registerJs' => false,
    ]);

    expect($html)->toContain('class="element-index')
        ->and($html)->toContain('class="toolbar flex"')
        ->and($html)->toContain('class="elements"');
});

it('renders a sidebar-less index when sources are disabled', function () {
    // The site menu resolves to `true` here (entries are localized), which used
    // to take the site-ID filtering path with a plain array of sources.
    $html = app(ElementIndexHtml::class)->html(Entry::class, [
        'sources' => false,
        'registerJs' => false,
    ]);

    expect($html)->toContain('class="sidebar hidden"')
        ->and($html)->toContain('__IMP__');
});

it('restricts the source list to the given source keys', function () {
    $html = app(ElementIndexHtml::class)->html(Entry::class, [
        'sources' => ['*'],
        'registerJs' => false,
    ]);

    expect($html)->toContain('data-key="*"');
});

it('includes footer in administrative contexts and omits it otherwise', function () {
    $indexHtml = app(ElementIndexHtml::class)->html(Entry::class, [
        'context' => 'index',
        'registerJs' => false,
    ]);
    $modalHtml = app(ElementIndexHtml::class)->html(Entry::class, [
        'context' => 'modal',
        'registerJs' => false,
    ]);

    expect($indexHtml)->toContain('class="footer flex flex-justify"')
        ->and($modalHtml)->not->toContain('class="footer flex flex-justify"');
});
