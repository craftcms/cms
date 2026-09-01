<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\HtmlSanitizers;
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

        expect($extension->markdownFilter('**bold**'))->toBe("<p><strong>bold</strong></p>\n");
        expect($extension->markdownFilter('**bold**', null, true))->toBe('<strong>bold</strong>');
        expect($extension->markdownFilter('`<b>`', null, false, true))->toBe("<p><code>&lt;b&gt;</code></p>\n");
        expect($extension->parseAttrFilter('not a tag'))->toBe([]);
        expect($extension->dataUrlFunction('/no/such/file.txt'))->toBe('');
    });

    it('dedents indented markdown so it is not parsed as a code block', function () {
        $extension = new HtmlTwigExtension;

        // The shape captured by an indented `{% apply md %}` block: every line
        // shares six spaces of leading indentation.
        $indented = "      ## Heading\n      Some text\n\n      - one\n      - two\n";

        expect($extension->markdownFilter($indented))
            ->toContain('<h2>Heading</h2>')
            ->toContain('<li>one</li>')
            ->not->toContain('<pre>');
    });

    it('preserves genuine code blocks when dedenting', function () {
        $extension = new HtmlTwigExtension;

        // Flush-left content, so the common indentation is zero and the
        // four-space code block keeps its relative indentation.
        $withCode = "Intro\n\n    genuine_code();\n\nOutro\n";

        expect($extension->markdownFilter($withCode))
            ->toContain('<pre><code>genuine_code();');
    });

    it('rejects custom flavors when encode is enabled', function () {
        $extension = new HtmlTwigExtension;

        $extension->markdownFilter('**bold**', 'gfm', false, true);
    })->throws(InvalidArgumentException::class, 'The Markdown flavor cannot be specified when passing `encode=true`.');

    it('sanitizes html with the default sanitizer', function () {
        $extension = new HtmlTwigExtension;

        $sanitized = $extension->sanitizeFilter('<p bad-attr="bad">Hello</p>');

        expect($sanitized)->toBe('<p>Hello</p>');
    });

    it('sanitizes html with a registered sanitizer name', function () {
        HtmlSanitizers::extend('links-only', new HtmlSanitizer((new HtmlSanitizerConfig)
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
