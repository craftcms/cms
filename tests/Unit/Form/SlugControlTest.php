<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\RequestedSite;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Ui\Controls\Slug;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

it('resolves and renders an editable slug with its source settings', function () {
    $payload = app(UiResolver::class)->resolve(
        Ui::make([
            Field::make('Title', Text::make('title')),
            Field::make('Slug', Slug::make('slug')->source('title')->charMap(['ø' => 'oe'])),
        ]),
        new UiContext(values: [
            'title' => 'Smørrebrød',
            'slug' => 'smoerrebroed',
        ]),
    );
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));
    $bodyHtml = HtmlStack::bodyHtml();

    expect($payload->nodes[1]->control?->props)->toBe([
        'source' => ['title'],
        'charMap' => ['ø' => 'oe'],
    ])
        ->and($crawler->filter('input[name="slug"][value="smoerrebroed"]'))->toHaveCount(1)
        ->and($bodyHtml)->toContain('new Craft.SlugGenerator')
        ->toContain('form-title-input')
        ->toContain('form-slug-input');
});

it('derives the character map from its language unless one is set', function () {
    $requestedSite = Mockery::mock(RequestedSite::class);
    $requestedSite->shouldReceive('get')->twice()->andReturn(new Site(['language' => 'de']));
    app()->instance(RequestedSite::class, $requestedSite);

    app()->setLocale('en');
    $differentLocale = Slug::make('slug')->source('title')->props();

    app()->setLocale('de');
    $matchingLocale = Slug::make('slug')->source('title')->props();
    $explicitLanguage = Slug::make('slug')->source('title')->language('de')->props();
    $custom = Slug::make('slug')
        ->source('title')
        ->language('de')
        ->charMap(['ä' => 'a'])
        ->props();

    expect($differentLocale['charMap']['ä'])->toBe('ae')
        ->and($matchingLocale['charMap']['ä'])->toBe('ae')
        ->and($explicitLanguage['charMap']['ä'])->toBe('ae')
        ->and($custom['charMap'])->toBe(['ä' => 'a']);
});

it('can expose its source without automatically generating', function () {
    $payload = app(UiResolver::class)->resolve(
        Ui::make([
            Field::make('Title', Text::make('title')),
            Field::make('Slug', Slug::make('slug')
                ->source('title')
                ->charMap([])
                ->autoGenerate(false)),
        ]),
        new UiContext(values: [
            'title' => 'Updated title',
            'slug' => 'established-slug',
        ]),
    );
    app(UiHtmlRenderer::class)->render($payload);

    expect($payload->nodes[1]->control?->props['autoGenerate'])->toBeFalse()
        ->and(HtmlStack::bodyHtml())->not->toContain('new Craft.SlugGenerator');
});

it('rejects invalid slug source paths', function (string|array $source) {
    Slug::make('slug')->source($source);
})->throws(InvalidArgumentException::class)
    ->with([
        'empty string' => '',
        'empty segment' => 'identity..title',
        'non-string segment' => [['identity', 1]],
    ]);
