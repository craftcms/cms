<?php

declare(strict_types=1);

use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\Group;
use Symfony\Component\DomCrawler\Crawler;

function visibilityPayload(Form $form): FormPayload
{
    return app(FormResolver::class)->resolve($form, new FormContext(
        values: ['subpath' => 'a/b'],
    ));
}

it('omits the hidden prop while a node is visible', function () {
    $payload = visibilityPayload(Form::make([
        Field::make('Subpath', Text::make('subpath')),
        Field::make('Explicit', Text::make('other'))->visible(),
    ]));

    expect($payload->nodes[0]->props)->not->toHaveKey('hidden')
        ->and($payload->nodes[1]->props)->not->toHaveKey('hidden');
});

it('keeps a hidden field resolved, with its value intact', function () {
    // The whole reason hiding isn't the same as omitting: an omitted node drops
    // its value from the payload, so it stops posting and the setting resets.
    $payload = visibilityPayload(Form::make([
        Field::make('Subpath', Text::make('subpath'))->visible(false),
    ]));

    expect($payload->nodes[0]->props['hidden'])->toBeTrue()
        ->and($payload->nodes[0]->control->path)->toBe(['subpath'])
        ->and($payload->values)->toBe(['subpath' => 'a/b']);
});

it('treats hidden(true) and visible(false) the same', function () {
    $payload = visibilityPayload(Form::make([
        Field::make('A', Text::make('a'))->hidden(),
        Field::make('B', Text::make('b'))->visible(false),
    ]));

    expect($payload->nodes[0]->props['hidden'])->toBeTrue()
        ->and($payload->nodes[1]->props['hidden'])->toBeTrue();
});

it('renders a hidden field with both the attribute and the class', function () {
    $payload = visibilityPayload(Form::make([
        Field::make('Subpath', Text::make('subpath'))->hidden(),
    ]));
    $field = new Crawler(app(FormHtmlRenderer::class)->render($payload))->filter('craft-field');

    expect($field->attr('hidden'))->not->toBeNull()
        ->and($field->attr('class'))->toContain('hidden')
        // Still in the DOM, so it still posts.
        ->and($field->filter('input[name="subpath"]'))->toHaveCount(1);
});

it('hides a group in either appearance, keeping its children resolved', function () {
    $children = fn (): array => [Field::make('Subpath', Text::make('subpath'))];

    $section = visibilityPayload(Form::make([
        Group::make('section', $children())->label('Section')->hidden(),
    ]));
    $field = visibilityPayload(Form::make([
        Group::make('as-field', $children())->asField()->label('As field')->hidden(),
    ]));

    $sectionHtml = new Crawler(app(FormHtmlRenderer::class)->render($section));
    $fieldHtml = new Crawler(app(FormHtmlRenderer::class)->render($field));

    expect($sectionHtml->filter('fieldset')->attr('hidden'))->not->toBeNull()
        ->and($fieldHtml->filter('craft-field[fieldset]')->attr('hidden'))->not->toBeNull()
        ->and($section->values)->toBe(['subpath' => 'a/b'])
        ->and($field->values)->toBe(['subpath' => 'a/b']);
});

it('exposes the current state through isVisible()', function () {
    expect(Field::make('A', Text::make('a'))->isVisible())->toBeTrue()
        ->and(Field::make('A', Text::make('a'))->hidden()->isVisible())->toBeFalse()
        ->and(Group::make('g')->visible(false)->isVisible())->toBeFalse();
});
