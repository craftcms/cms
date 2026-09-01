<?php

declare(strict_types=1);

use CraftCms\Cms\Form\Controls\ContentBlock;
use CraftCms\Cms\Form\Controls\Matrix;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use Symfony\Component\DomCrawler\Crawler;

function nestedControlsForm(): Form
{
    $contentBlock = ContentBlock::make('content')->form(Form::make([
        Field::make('Body', Text::make('body')),
    ]));

    return Form::make([
        Field::make('Content',
            Matrix::make('matrix')
                ->entryTypes(['text' => 'Text'])
                ->forms([
                    'block-a' => Form::make([
                        Field::make('Heading', Text::make('heading')),
                        Field::make('Content block', $contentBlock),
                    ]),
                ]),
        ),
    ]);
}

function nestedControlsContext(): FormContext
{
    return new FormContext(
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
    $payload = app(FormResolver::class)->resolve(nestedControlsForm(), nestedControlsContext());
    $matrix = $payload->nodes[0]->control;
    $entryForm = $matrix->forms[0];
    $contentBlock = $entryForm->nodes[1]->control;
    $contentBlockForm = $contentBlock->forms[0];
    $body = $contentBlockForm->nodes[0]->control;

    expect($matrix->component)->toBe('craft:matrix')
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
    $payload = app(FormResolver::class)->resolve(nestedControlsForm(), nestedControlsContext());
    $scope = ['settings', 'matrix', 'entries', 'block-a'];
    $nested = $payload->forScope($scope);

    expect($nested->scope)->toBe($scope)
        ->and($nested->nodes)->toHaveCount(2)
        ->and($nested->values)->toBe($payload->values)
        ->and($nested->errors)->toBe($payload->errors)
        ->and($nested->globalErrors)->toBe([]);
});

it('renders nested Controls with Craft web components and no nested forms', function () {
    $payload = app(FormResolver::class)->resolve(nestedControlsForm(), nestedControlsContext());
    $crawler = new Crawler('<form>'.app(FormHtmlRenderer::class)->render($payload).'</form>');

    expect($crawler->filter('form form'))->toHaveCount(0)
        ->and($crawler->filter('craft-matrix-input .matrixblock[data-id="block-a"]'))->toHaveCount(1)
        ->and($crawler->filter('.pane[data-content-block]'))->toHaveCount(1)
        ->and($crawler->filter('craft-reorder-button'))->toHaveCount(1)
        ->and($crawler->filter('craft-button[data-form-matrix-remove]'))->toHaveCount(1)
        ->and($crawler->filter('input[name="settings[matrix][sortOrder][]"][value="block-a"]'))->toHaveCount(1)
        ->and($crawler->filter('input[name="settings[matrix][entries][block-a][heading]"][value="Welcome"]'))->toHaveCount(1)
        ->and($crawler->filter('input[name="settings[matrix][entries][block-a][content][body]"][value="Nested body"]'))->toHaveCount(1)
        ->and($crawler->text())->toContain('Body is invalid.');
});

it('uses explicit empty canonical values', function () {
    $form = Form::make([
        Field::make()->control(Matrix::make('matrix')->entryTypes(['text' => 'Text'])),
        Field::make()->control(ContentBlock::make('content')),
    ]);
    $payload = app(FormResolver::class)->resolve($form, new FormContext(namespace: 'settings'));

    expect($payload->values)->toBe(['settings' => [
        'matrix' => ['entries' => [], 'sortOrder' => []],
        'content' => null,
    ]]);
});
