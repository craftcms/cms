<?php

declare(strict_types=1);

use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers;
use CraftCms\Cms\Twig\Extensions\HtmlTwigExtension;
use CraftCms\Cms\Twig\Twig;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

beforeEach(function () {
    $this->env = app(Twig::class)->create();
});

function htmlFilterNames(array $filters): array
{
    return array_map(fn ($filter) => $filter->getName(), $filters);
}

function htmlFunctionNames(array $functions): array
{
    return array_map(fn ($function) => $function->getName(), $functions);
}

describe('HtmlTwigExtension', function () {
    it('registers expected filters and functions', function () {
        $extension = new HtmlTwigExtension;

        expect(htmlFilterNames($extension->getFilters()))->toContain(
            'append',
            'attr',
            'markdown',
            'parseAttr',
            'removeClass',
            'sanitize',
        );
        expect(htmlFunctionNames($extension->getFunctions()))->toContain('dataUrl', 'svg', 'tag', 'actionInput');
    });

    it('supports HTML helper behavior', function () {
        $extension = new HtmlTwigExtension;

        $tag = $extension->tagFunction('span', ['class' => 'x', 'text' => 'hi']);
        $modified = $extension->attrFilter('<div></div>', ['class' => 'box']);
        $appended = $extension->appendFilter('<div></div>', '<span>child</span>');

        expect($tag)->toContain('<span')->toContain('class="x"')->toContain('hi');
        expect($modified)->toContain('class="box"');
        expect($appended)->toContain('<span>child</span>');
    });

    it('handles markdown and parseAttr edge cases', function () {
        $extension = new HtmlTwigExtension;

        expect($extension->markdownFilter('**bold**'))->toContain('<strong>');
        expect($extension->parseAttrFilter('not a tag'))->toBe([]);
        expect($extension->dataUrlFunction('/no/such/file.txt'))->toBe('');
    });

    it('sanitizes html with the default sanitizer', function () {
        $extension = new HtmlTwigExtension;

        $sanitized = $extension->sanitizeFilter('<p bad-attr="bad">Hello</p>');

        expect($sanitized)->toBe('<p>Hello</p>');
    });

    it('sanitizes html with a registered sanitizer name', function () {
        app(HtmlSanitizers::class)->register('links-only', new HtmlSanitizer((new HtmlSanitizerConfig)
            ->allowElement('a')
            ->allowAttribute('href', ['a'])
        ));

        $extension = new HtmlTwigExtension;
        $sanitized = $extension->sanitizeFilter('<a href="https://craftcms.com" onclick="bad()">Craft</a><p>bad</p>', 'links-only');

        expect($sanitized)->toBe('<a href="https://craftcms.com">Craft</a>');
    });

    it('sanitizes html with a sanitizer instance', function () {
        $extension = new HtmlTwigExtension;
        $sanitizer = new HtmlSanitizer((new HtmlSanitizerConfig)
            ->allowElement('p')
            ->allowAttribute('class', ['p']));

        $sanitized = $extension->sanitizeFilter('<p class="lead">Hello</p>', $sanitizer);

        expect($sanitized)->toBe('<p class="lead">Hello</p>');
    });
});
