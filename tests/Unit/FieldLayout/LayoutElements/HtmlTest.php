<?php

declare(strict_types=1);

use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\Html;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use Symfony\Component\DomCrawler\Crawler;

it('renders sanitized non-interactive HTML content', function () {
    $element = new Html('<p>Direct HTML</p><form><input value="unsafe"></form><script>alert(1)</script>', [
        'uid' => 'html-test',
    ]);
    $context = new FormContext;
    $node = $element->formNode(new FieldLayoutElementContext(null, $context));
    $payload = app(FormResolver::class)->resolve(Form::make([$node]), $context);
    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));

    expect($crawler->filter('[data-form-node="html-test"][inert]'))->toHaveCount(1)
        ->and($crawler->filter('p')->text())->toBe('Direct HTML')
        ->and($crawler->filter('form, input, script'))->toHaveCount(0);
});
