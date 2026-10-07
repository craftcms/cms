<?php

declare(strict_types=1);

use CraftCms\Cms\Ui\Controls\ContentBlock;
use CraftCms\Cms\Ui\Controls\NestedElementBlocks;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Tab;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

function nestedControlsUi(): Ui
{
    $contentBlock = ContentBlock::make('content')->ui(Ui::make([
        Field::make('Body', Text::make('body')),
    ]));

    return Ui::make([
        Field::make('Content',
            NestedElementBlocks::make('matrix')
                ->entryTypes(['text' => 'Text'])
                ->uis([
                    'block-a' => Ui::make([
                        Field::make('Heading', Text::make('heading')),
                        Field::make('Content block', $contentBlock),
                    ]),
                ]),
        ),
    ]);
}

function nestedControlsContext(): UiContext
{
    return new UiContext(
        namespace: 'settings',
        values: ['settings' => ['matrix' => [
            'entries' => ['block-a' => [
                'type' => 'text',
                'heading' => 'Welcome',
                'content' => ['body' => 'Nested body'],
            ]],
            'sortOrder' => ['block-a'],
        ]]],
        errors: ['matrix.entries.block-a.content.body' => ['Body is invalid.']],
    );
}

it('resolves nested Form scopes recursively with one ancestor atomic group', function () {
    $payload = app(UiResolver::class)->resolve(nestedControlsUi(), nestedControlsContext());
    $matrix = $payload->nodes[0]->control;
    $entryForm = $matrix->uis[0];
    $contentBlock = $entryForm->nodes[1]->control;
    $contentBlockForm = $contentBlock->uis[0];
    $body = $contentBlockForm->nodes[0]->control;

    expect($matrix->component)->toBe('craft:nested-element-blocks')
        ->and($entryForm->scope)->toBe(['settings', 'matrix', 'entries', 'block-a'])
        ->and($entryForm->refreshable)->toBeTrue()
        ->and($contentBlock->component)->toBe('craft:content-block')
        ->and($contentBlockForm->scope)->toBe(['settings', 'matrix', 'entries', 'block-a', 'content'])
        ->and($body->path)->toBe(['settings', 'matrix', 'entries', 'block-a', 'content', 'body'])
        ->and($body->deltaGroup)->toBe(['settings', 'matrix'])
        ->and($payload->errors)->toBe([[
            'path' => $body->path,
            'messages' => ['Body is invalid.'],
        ]]);
});

it('returns a nested Form payload for a dependent refresh scope', function () {
    $payload = app(UiResolver::class)->resolve(nestedControlsUi(), nestedControlsContext());
    $scope = ['settings', 'matrix', 'entries', 'block-a'];
    $nested = $payload->forScope($scope);

    expect($nested->scope)->toBe($scope)
        ->and($nested->nodes)->toHaveCount(2)
        ->and($nested->values)->toBe($payload->values)
        ->and($nested->errors)->toBe($payload->errors)
        ->and($nested->globalErrors)->toBe([]);
});

it('mounts an isolated nested control with its values, scopes, and validation errors in an HTML form', function () {
    $context = nestedControlsContext();
    $values = $context->values;
    $values['settings']['other'] = 'Unrelated owner text';
    $payload = app(UiResolver::class)->resolve(
        nestedControlsUi()->add(Field::make('Other', Text::make('other'))),
        new UiContext(
            namespace: $context->namespace,
            values: $values,
            errors: [...$context->errors, 'other' => ['Other is invalid.']],
            globalErrors: ['Owner is invalid.'],
        ),
    );
    $crawler = new Crawler('<form>'.app(UiHtmlRenderer::class)->render($payload).'</form>');
    $host = $crawler->filter('craft-entry-field-layout-ui[data-field-path]');
    $form = json_decode($host->attr('data-payload'), true, flags: JSON_THROW_ON_ERROR);

    expect($crawler->filter('form form'))->toHaveCount(0)
        ->and($host)->toHaveCount(1)
        ->and($form['scope'])->toBe(['settings'])
        ->and(array_keys($form['values']['settings']))->toBe(['matrix'])
        ->and($form['nodes'])->toHaveCount(1)
        ->and($form['values']['settings']['matrix']['entries']['block-a'])->toMatchArray([
            'heading' => 'Welcome',
            'content' => ['body' => 'Nested body'],
        ])
        ->and($form['values']['settings']['matrix']['sortOrder'])->toBe(['block-a'])
        ->and($form['nodes'][0]['control']['component'])->toBe('craft:nested-element-blocks')
        ->and($form['nodes'][0]['control']['uis'][0]['scope'])->toBe(['settings', 'matrix', 'entries', 'block-a'])
        ->and($form['errors'])->toBe([[
            'path' => ['settings', 'matrix', 'entries', 'block-a', 'content', 'body'],
            'messages' => ['Body is invalid.'],
        ]])
        ->and($form['globalErrors'])->toBe([])
        ->and($host->filter('input[data-ui-field-name]')->attr('name'))->toBe('settings[matrix]')
        ->and($host->filter('input[name]:not([disabled])'))->toHaveCount(0);
});

function nestedTabsCrawler(): Crawler
{
    $nested = Ui::make([
        Tab::make('content', 'Content', [Field::make('Body', Text::make('body'))]),
        Tab::make('details', 'Details', [Field::make('Summary', Text::make('summary'))]),
    ]);
    $form = Ui::make([
        Tab::make('content', 'Content', [
            Field::make('Hero', ContentBlock::make('hero')->ui($nested)),
            Field::make('Footer', ContentBlock::make('footer')->ui($nested)),
        ]),
        Tab::make('settings', 'Settings', [Field::make('Title', Text::make('title'))]),
    ]);
    $payload = app(UiResolver::class)->resolve($form, new UiContext(
        namespace: 'settings',
        values: ['settings' => [
            'hero' => ['body' => 'Hero body', 'summary' => 'Hero summary'],
            'footer' => ['body' => 'Footer body', 'summary' => 'Footer summary'],
            'title' => 'Page title',
        ]],
    ));

    return new Crawler(app(UiHtmlRenderer::class)->render($payload));
}

it('shows the first tab in each nested HTML form independently of its parent', function () {
    $crawler = nestedTabsCrawler();

    expect($crawler->filter('craft-content-block-input section[data-ui-tab="content"]:not(.hidden)'))->toHaveCount(2)
        ->and($crawler->filter('craft-content-block-input section[data-ui-tab="details"].hidden'))->toHaveCount(2)
        ->and($crawler->filter('section[data-ui-tab="content"]:not(.hidden) input[name="settings[hero][body]"][value="Hero body"]'))->toHaveCount(1)
        ->and($crawler->filter('section[data-ui-tab="content"]:not(.hidden) input[name="settings[footer][body]"][value="Footer body"]'))->toHaveCount(1)
        ->and($crawler->filter('section#settings-form-tab-content:not(.hidden)'))->toHaveCount(1)
        ->and($crawler->filter('section#settings-form-tab-settings.hidden input[value="Page title"]'))->toHaveCount(1);
});

it('gives nested HTML tab panels distinct IDs across instances and their parent', function () {
    $crawler = nestedTabsCrawler();
    $ids = $crawler->filter('section[data-ui-tab]')->extract(['id']);

    expect($ids)->toHaveCount(6)
        ->and(array_unique($ids))->toHaveCount(6)
        ->and($crawler->filter('section#settings-hero-form-tab-content[aria-label="Content"]'))->toHaveCount(1)
        ->and($crawler->filter('section#settings-footer-form-tab-content[aria-label="Content"]'))->toHaveCount(1)
        ->and($crawler->filter('section#settings-hero-form-tab-details[aria-label="Details"]'))->toHaveCount(1)
        ->and($crawler->filter('section#settings-footer-form-tab-details[aria-label="Details"]'))->toHaveCount(1);
});

it('declares which Controls hold nested forms', function () {
    $payload = app(UiResolver::class)->resolve(nestedControlsUi(), nestedControlsContext());
    $matrix = $payload->nodes[0]->control;
    $contentBlock = $matrix->uis[0]->nodes[1]->control;
    $heading = $matrix->uis[0]->nodes[0]->control;

    // A change inside one of these marks the field holding it, so the browser
    // has to be told which they are. Leaf Controls ship nothing.
    expect($matrix->nestsUis)->toBeTrue()
        ->and($matrix->jsonSerialize())->toHaveKey('nestsUis', true)
        ->and($contentBlock->nestsUis)->toBeTrue()
        ->and($heading->nestsUis)->toBeFalse()
        ->and($heading->jsonSerialize())->not->toHaveKey('nestsUis');
});

it('uses explicit empty canonical values', function () {
    $form = Ui::make([
        Field::make()->control(NestedElementBlocks::make('matrix')->entryTypes(['text' => 'Text'])),
        Field::make()->control(ContentBlock::make('content')),
    ]);
    $payload = app(UiResolver::class)->resolve($form, new UiContext(namespace: 'settings'));

    expect($payload->values)->toBe(['settings' => [
        'matrix' => ['entries' => [], 'sortOrder' => []],
        'content' => null,
    ]]);
});
