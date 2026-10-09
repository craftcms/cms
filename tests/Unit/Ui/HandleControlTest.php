<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Ui\Controls\Handle;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

it('resolves and renders an editable handle with its relative source path', function () {
    $payload = app(UiResolver::class)->resolve(
        Ui::make([
            Field::make('Name', Text::make('identity.name')),
            Field::make('Handle', Handle::make('identity.handle')
                ->source('name')
                ->maxLength(64)
                ->placeholder('exampleHandle'))->required(),
        ]),
        new UiContext(
            namespace: 'settings',
            values: ['settings' => ['identity' => [
                'name' => 'Example handle',
                'handle' => 'exampleHandle',
            ]]],
            errors: ['identity.handle' => ['Choose another handle.']],
        ),
    );
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));
    $bodyHtml = HtmlStack::bodyHtml();

    expect($payload->nodes[1]->control?->props)->toBe([
        'source' => ['name'],
        'maxLength' => 64,
        'placeholder' => 'exampleHandle',
    ])
        ->and($crawler->filter('input[name="settings[identity][name]"][value="Example handle"]'))->toHaveCount(1)
        ->and($crawler->filter('craft-input-handle input[name="settings[identity][handle]"][value="exampleHandle"][maxlength="64"][placeholder="exampleHandle"][required][aria-invalid="true"]'))->toHaveCount(1)
        ->and($bodyHtml)->toContain('new Craft.HandleGenerator')
        ->toContain('ui-settings-identity-name-input')
        ->toContain('ui-settings-identity-handle-input');
});

it('displays handle values without submitting them in non-editable modes', function (ControlMode $mode) {
    $payload = app(UiResolver::class)->resolve(
        Ui::make([Field::make('Handle', Handle::make('handle'))]),
        new UiContext(
            namespace: 'settings',
            values: ['settings' => ['handle' => 'exampleHandle']],
            mode: $mode,
        ),
    );
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));

    expect($crawler->filter('craft-input-handle input[value="exampleHandle"]'))->toHaveCount(1)
        ->and($crawler->filter('[name]'))->toHaveCount(0);
})->with([
    'read-only' => ControlMode::ReadOnly,
    'disabled' => ControlMode::Disabled,
]);

it('rejects invalid handle source paths', function (string|array $source) {
    Handle::make('handle')->source($source);
})->throws(InvalidArgumentException::class)
    ->with([
        'empty string' => '',
        'empty segment' => 'identity..name',
        'non-string segment' => [['identity', 1]],
    ]);
