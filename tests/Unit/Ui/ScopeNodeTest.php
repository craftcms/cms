<?php

declare(strict_types=1);

use CraftCms\Cms\Ui\Controls\ContentBlock;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\Nodes\Scope;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

it('embeds reusable component settings under separate paths without changing the component', function () {
    $apiKey = Text::make('apiKey')->value('default');
    $settings = Group::make('credentials', [Field::make('API key', $apiKey)]);
    $ui = Ui::make([
        Field::make('Title', Text::make('title')),
        Scope::make('primary', [Scope::make('settings', [$settings])->deltaGroupAtNamespace()]),
        Scope::make(['secondary', 'settings'], [$settings]),
    ]);
    $payload = app(UiResolver::class)->resolve($ui, new UiContext(
        namespace: 'fields',
        values: ['fields' => [
            'title' => 'Page',
            'primary' => ['settings' => ['apiKey' => 'submitted']],
        ]],
        errors: ['primary.settings.apiKey' => ['Enter a valid API key.']],
    ));
    $controls = collect(flattenUiNodes(array_map(fn ($node): array => $node->jsonSerialize(), $payload->nodes)))
        ->pluck('control')->filter()->keyBy(fn (array $control): string => implode('.', $control['path']));

    expect($controls->keys()->all())->toContain(
        'fields.primary.settings.apiKey',
        'fields.secondary.settings.apiKey',
        'fields.title',
    )
        ->and($controls['fields.primary.settings.apiKey']['deltaGroup'])->toBe(['fields', 'primary', 'settings'])
        ->and($controls['fields.secondary.settings.apiKey']['deltaGroup'])->toBe(['fields', 'secondary', 'settings', 'apiKey'])
        ->and($payload->values)->toBe(['fields' => [
            'title' => 'Page',
            'primary' => ['settings' => ['apiKey' => 'submitted']],
            'secondary' => ['settings' => ['apiKey' => 'default']],
        ]])
        ->and($payload->errors)->toBe([[
            'path' => ['fields', 'primary', 'settings', 'apiKey'],
            'messages' => ['Enter a valid API key.'],
        ]])
        ->and($apiKey->path())->toBe('apiKey');
});

it('renders scoped inputs with their labels and validation errors without a wrapper', function () {
    $payload = app(UiResolver::class)->resolve(Ui::make([
        Scope::make('settings', [
            Field::make('API key', Text::make('apiKey'))->required(),
            Field::make('Endpoint', Text::make('endpoint')),
        ]),
    ]), new UiContext(
        values: ['settings' => ['apiKey' => 'invalid', 'endpoint' => 'https://example.com']],
        errors: ['settings.apiKey' => ['Enter a valid API key.']],
    ));
    $crawler = new Crawler('<form>'.app(UiHtmlRenderer::class)->render($payload).'</form>');
    $input = $crawler->filter('input[name="settings[apiKey]"]');

    expect($crawler->filter('form > craft-field'))->toHaveCount(2)
        ->and($input->attr('value'))->toBe('invalid')
        ->and($input->attr('aria-invalid'))->toBe('true')
        ->and($input->attr('required'))->not->toBeNull()
        ->and($crawler->filter('craft-field')->first()->attr('label'))->toBe('API key')
        ->and($crawler->filter('input[name="settings[endpoint]"]')->attr('value'))->toBe('https://example.com')
        ->and($crawler->text())->toContain('Enter a valid API key.');
});

it('keeps an enclosing nested control atomic when a scope declares a smaller delta group', function () {
    $payload = app(UiResolver::class)->resolve(Ui::make([
        Field::make('Content', ContentBlock::make('content')->ui(Ui::make([
            Scope::make('settings', [
                Field::make('API key', Text::make('apiKey')),
            ])->deltaGroupAtNamespace(),
        ]))),
    ]), new UiContext(values: ['content' => ['settings' => ['apiKey' => 'saved']]]));
    $nestedUi = $payload->forScope(['content']);
    $controls = collect(flattenUiNodes(array_map(fn ($node): array => $node->jsonSerialize(), $nestedUi->nodes)))
        ->pluck('control')->filter()->keyBy(fn (array $control): string => implode('.', $control['path']));

    expect($controls['content.settings.apiKey']['deltaGroup'])->toBe(['content'])
        ->and($payload->values)->toBe(['content' => ['settings' => ['apiKey' => 'saved']]]);
});
