<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\Template;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\View\TemplateMode;
use Symfony\Component\DomCrawler\Crawler;

it('renders sanitized non-interactive template content', function () {
    $templatesPath = Aliases::getAll()['@templates'] ?? null;

    try {
        Aliases::set('@templates', dirname(__DIR__, 3).'/Support/templates');

        $element = new Template([
            'uid' => 'template-test',
            'template' => 'field-layout-template',
            'templateMode' => TemplateMode::Site->value,
            'width' => 50,
        ]);
        $context = new FormContext;
        $node = $element->formNode(new FieldLayoutElementContext(null, $context));
    } finally {
        $templatesPath === null
            ? Aliases::remove('@templates')
            : Aliases::set('@templates', $templatesPath);
    }

    $payload = app(FormResolver::class)->resolve(Form::make([$node]), $context);
    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));

    expect($crawler->filter('[data-form-node="template-test"][inert].width-50'))->toHaveCount(1)
        ->and($crawler->filter('.template-content')->text())->toBe('Display only')
        ->and($crawler->filter('form, input, button, script'))->toHaveCount(0)
        ->and(HtmlStack::headHtml(false))->not->toContain('template-content')
        ->and(HtmlStack::bodyHtml(false))->not->toContain('templateAssetRan');
});
