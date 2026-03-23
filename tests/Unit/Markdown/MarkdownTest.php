<?php

declare(strict_types=1);

use CraftCms\Cms\Markdown\Flavors\GfmFlavor;
use CraftCms\Cms\Markdown\Markdown;
use CraftCms\Cms\Markdown\MarkdownOptions;

beforeEach(function () {
    $this->markdown = app(Markdown::class);
});

describe('Markdown', function () {
    it('renders block markdown', function () {
        expect($this->markdown->parse('**bold**'))->toBe("<p><strong>bold</strong></p>\n");
    });

    it('renders inline-only markdown', function () {
        expect($this->markdown->parseParagraph('**bold**'))->toBe("<strong>bold</strong>\n");
    });

    it('uses comment line breaks for gfm-comment', function () {
        expect($this->markdown->parse("line one\nline two", 'gfm-comment'))
            ->toBe("<p>line one<br>\nline two</p>\n");
    });

    it('relies on commonmark unsafe link handling by default', function () {
        expect($this->markdown->parse('[test](javascript:alert(1))'))
            ->toBe("<p><a>test</a></p>\n");
    });

    it('can allow unsafe links for compatibility shims', function () {
        expect($this->markdown->parse('[test](javascript:alert(1))', allowUnsafeLinks: true))
            ->toBe("<p><a href=\"javascript:alert(1)\">test</a></p>\n");
    });

    it('does not double-escape pre-encoded inline code', function () {
        expect($this->markdown->parse('`&lt;b&gt;`', 'pre-encoded'))
            ->toBe("<p><code>&lt;b&gt;</code></p>\n");
    });

    it('does not double-escape pre-encoded fenced code', function () {
        $markdown = "```html\n&lt;b&gt;\n```";

        expect($this->markdown->parse($markdown, 'pre-encoded'))
            ->toContain('<pre><code class="language-html">&lt;b&gt;');
    });

    it('supports extending flavors with lazy callables', function () {
        $calls = 0;

        $flavor = new GfmFlavor("<br>\n");

        $this->markdown->extend('custom', function (MarkdownOptions $options) use (&$calls, $flavor) {
            $calls++;

            return $flavor($options);
        });

        expect($this->markdown->parse("one\ntwo", 'custom'))
            ->toBe("<p>one<br>\ntwo</p>\n")
            ->and($this->markdown->parse('**bold**', 'custom'))
            ->toBe("<p><strong>bold</strong></p>\n")
            ->and($calls)->toBe(1);
    });

    it('throws for unknown flavors', function () {
        $this->markdown->parse('text', 'missing');
    })->throws(InvalidArgumentException::class, 'Unknown Markdown flavor [missing].');
});
