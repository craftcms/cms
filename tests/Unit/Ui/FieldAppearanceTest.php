<?php

declare(strict_types=1);

use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Illuminate\Support\HtmlString;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Markup;

function presentationField(bool $asGroup): Field|Group
{
    return $asGroup
        ? Group::make('title-group', [Field::make('Title input', Text::make('title'))])->asField()
        : Field::make(null, Text::make('title'));
}

it('renders an info icon beside the field label', function (bool $htmlable, bool $asGroup) {
    $suffix = '<craft-info-icon label="More info about Title">Shown in the entry index.</craft-info-icon>';
    $payload = app(UiResolver::class)->resolve(
        Ui::make([
            presentationField($asGroup)->label('Title')
                ->headingSuffix($htmlable ? new HtmlString($suffix) : $suffix),
        ]),
        new UiContext,
    );
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));
    $icon = $crawler->filter('craft-field [slot="heading-suffix"]');

    expect($icon->nodeName())->toBe('craft-info-icon')
        ->and($icon->attr('label'))->toBe('More info about Title')
        ->and($icon->text())->toBe('Shown in the entry index.')
        ->and($crawler->filter('craft-field')->first()->attr('label'))->toBe('Title');
})->with([false, true])->with(['field' => false, 'group' => true]);

it('renders field presentation settings independently of grid width', function (bool $twigMarkup, bool $asGroup) {
    $label = '<strong>Title &copy; subtitle</strong>';
    $payload = app(UiResolver::class)->resolve(
        Ui::make([
            presentationField($asGroup)
                ->label($twigMarkup ? new Markup($label, 'UTF-8') : new HtmlString($label))
                ->labelSrOnly()
                ->headingPrefix(new HtmlString('<span>Before</span>'))
                ->translatable(description: 'Translated for each site.')
                ->orientation('rtl')
                ->width(50)
                ->inputWidth('full'),
        ]),
        new UiContext,
    );
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));
    $field = $crawler->filter('craft-field')->first();

    expect($field->filter('strong[slot="label"]')->text())->toBe('Title © subtitle')
        ->and($field->filter('[slot="heading-prefix"]')->text())->toBe('Before')
        ->and($field->attr('translatable'))->not->toBeNull()
        ->and($field->attr('translation-description'))->toBe('Translated for each site.')
        ->and($field->attr('orientation'))->toBe('rtl')
        ->and($field->attr('width'))->toBe('full')
        ->and($field->attr('class'))->toContain('width-50')
        ->and($field->attr('label-sr-only'))->not->toBeNull()
        ->and($payload->nodes[0]->props['label'])->toBe('Title © subtitle');
})->with([false, true])->with(['field' => false, 'group' => true]);

it('clears markup when replacing a label with plain text', function (bool $asGroup) {
    $payload = app(UiResolver::class)->resolve(
        Ui::make([
            presentationField($asGroup)->label(new HtmlString('<strong>Title</strong>'))
                ->label('<em>Plain text</em>'),
        ]),
        new UiContext,
    );
    $field = new Crawler(app(UiHtmlRenderer::class)->render($payload))->filter('craft-field')->first();

    expect($field->attr('label'))->toBe('<em>Plain text</em>')
        ->and($field->filter('[slot="label"]'))->toHaveCount(0);
})->with(['field' => false, 'group' => true]);

it('shows or suppresses an explicit field status', function (bool $showStatus, bool $asGroup) {
    $payload = app(UiResolver::class)->resolve(Ui::make([
        presentationField($asGroup)->label('Title')->status('modified', 'Title changed')->showStatus($showStatus),
    ]), new UiContext);
    $field = new Crawler(app(UiHtmlRenderer::class)->render($payload))->filter('craft-field')->first();

    expect($field->attr('status'))->toBe($showStatus ? 'modified' : null)
        ->and($field->attr('status-label'))->toBe($showStatus ? 'Title changed' : null);
})->with([false, true])->with(['field' => false, 'group' => true]);
