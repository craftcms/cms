<?php

declare(strict_types=1);

use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use Symfony\Component\DomCrawler\Crawler;

function renderField(string $path = 'title'): Crawler
{
    $payload = app(FormResolver::class)->resolve(
        Form::make([Field::make('Title', Text::make($path))]),
        new FormContext,
    );

    return new Crawler(app(FormHtmlRenderer::class)->render($payload));
}

it('gives the field the id derived from the control path', function () {
    expect(renderField()->filter('craft-field')->attr('id'))->toBe('form-title');
});

it('puts the input inside on a distinct id of its own', function () {
    // The label points `for` at this, and Handle's generator targets it by id.
    $input = renderField()->filter('craft-input input');

    expect($input->attr('id'))->toBe('form-title-input')
        ->and($input->attr('name'))->toBe('title');
});

it('keeps the field id unique from the input id', function () {
    $crawler = renderField();

    expect($crawler->filter('craft-field')->attr('id'))
        ->not->toBe($crawler->filter('craft-input input')->attr('id'));
});
