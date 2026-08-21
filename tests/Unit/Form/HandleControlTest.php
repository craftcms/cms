<?php

declare(strict_types=1);

use CraftCms\Cms\Form\Controls\Handle;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Facades\HtmlStack;
use Symfony\Component\DomCrawler\Crawler;

it('resolves and renders an editable handle with its relative source path', function () {
    $payload = app(FormResolver::class)->resolve(
        Form::make([
            Field::make('Name', Text::make('identity.name')),
            Field::make('Handle', Handle::make('identity.handle')->source('name'))->required(),
        ]),
        new FormContext(
            namespace: 'settings',
            values: ['settings' => ['identity' => [
                'name' => 'Example handle',
                'handle' => 'exampleHandle',
            ]]],
            errors: ['identity.handle' => ['Choose another handle.']],
        ),
    );
    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));
    $bodyHtml = HtmlStack::bodyHtml();

    expect($payload->nodes[1]->control?->props)->toBe(['source' => ['name']])
        ->and($crawler->filter('input[name="settings[identity][name]"][value="Example handle"]'))->toHaveCount(1)
        ->and($crawler->filter('craft-input-handle input[name="settings[identity][handle]"][value="exampleHandle"][required][aria-invalid="true"]'))->toHaveCount(1)
        ->and($bodyHtml)->toContain('new Craft.HandleGenerator')
        ->toContain('form-settings-identity-name')
        ->toContain('form-settings-identity-handle');
});

it('displays handle values without submitting them in non-editable modes', function (ControlMode $mode) {
    $payload = app(FormResolver::class)->resolve(
        Form::make([Field::make('Handle', Handle::make('handle'))]),
        new FormContext(
            namespace: 'settings',
            values: ['settings' => ['handle' => 'exampleHandle']],
            mode: $mode,
        ),
    );
    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));

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
