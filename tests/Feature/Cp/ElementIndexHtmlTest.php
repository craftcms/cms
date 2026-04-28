<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Html\ElementIndexHtml;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
});

it('renders an element index shell with toolbar and elements container', function () {
    $html = app(ElementIndexHtml::class)->html(Entry::class, [
        'registerJs' => false,
    ]);

    expect($html)->toContain('class="element-index')
        ->and($html)->toContain('class="toolbar flex"')
        ->and($html)->toContain('class="elements"');
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
