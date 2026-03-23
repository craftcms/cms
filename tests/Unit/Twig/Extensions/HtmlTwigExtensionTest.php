<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Extensions\HtmlTwigExtension;
use CraftCms\Cms\Twig\PageLifecycle;
use CraftCms\Cms\Twig\Twig;

beforeEach(function () {
    $this->pageLifecycle = app(PageLifecycle::class);
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
        $extension = new HtmlTwigExtension($this->pageLifecycle, $this->env);

        expect(htmlFilterNames($extension->getFilters()))->toContain(
            'append',
            'attr',
            'markdown',
            'parseAttr',
            'purify',
            'removeClass',
        );
        expect(htmlFunctionNames($extension->getFunctions()))->toContain('dataUrl', 'svg', 'tag', 'actionInput');
    });

    it('supports HTML helper behavior', function () {
        $extension = new HtmlTwigExtension($this->pageLifecycle, $this->env);

        $tag = $extension->tagFunction('span', ['class' => 'x', 'text' => 'hi']);
        $modified = $extension->attrFilter('<div></div>', ['class' => 'box']);
        $appended = $extension->appendFilter('<div></div>', '<span>child</span>');

        expect($tag)->toContain('<span')->toContain('class="x"')->toContain('hi');
        expect($modified)->toContain('class="box"');
        expect($appended)->toContain('<span>child</span>');
    });

    it('handles markdown and parseAttr edge cases', function () {
        $extension = new HtmlTwigExtension($this->pageLifecycle, $this->env);

        expect($extension->markdownFilter('**bold**'))->toBe("<p><strong>bold</strong></p>\n");
        expect($extension->markdownFilter('**bold**', null, true))->toBe('<strong>bold</strong>');
        expect($extension->markdownFilter('`<b>`', null, false, true))->toBe("<p><code>&lt;b&gt;</code></p>\n");
        expect($extension->parseAttrFilter('not a tag'))->toBe([]);
        expect($extension->dataUrlFunction('/no/such/file.txt'))->toBe('');
    });

    it('rejects custom flavors when encode is enabled', function () {
        $extension = new HtmlTwigExtension($this->pageLifecycle, $this->env);

        $extension->markdownFilter('**bold**', 'gfm', false, true);
    })->throws(InvalidArgumentException::class, 'The Markdown flavor cannot be specified when passing `encode=true`.');
});
