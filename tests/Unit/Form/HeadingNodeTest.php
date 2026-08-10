<?php

declare(strict_types=1);

use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Heading;
use Symfony\Component\DomCrawler\Crawler;

it('renders the configured heading level and defaults to level two', function () {
    $form = Form::make([
        Heading::make('custom', 'Custom')->level(3),
        Heading::make('default', 'Default'),
    ]);
    $payload = app(FormResolver::class)->resolve($form, new FormContext);
    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));

    expect($payload->nodes[0]->props['level'])->toBe(3)
        ->and($crawler->filter('h3[data-form-node="custom"]')->text())->toBe('Custom')
        ->and($payload->nodes[1]->props['level'])->toBe(2)
        ->and($crawler->filter('h2[data-form-node="default"]')->text())->toBe('Default');
});
