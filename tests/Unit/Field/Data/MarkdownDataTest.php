<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Data\MarkdownData;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\HtmlSanitizers;
use CraftCms\Cms\Twig\Contracts\SafeHtml;
use Illuminate\Contracts\Support\Htmlable;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

it('exposes raw markdown and renders html with the configured flavor', function () {
    $value = new MarkdownData("line one\nline two", 'gfm-comment');

    expect($value)->toBeInstanceOf(SafeHtml::class)
        ->and($value)->toBeInstanceOf(Htmlable::class)
        ->and($value)->toBeInstanceOf(Stringable::class)
        ->and($value->getRaw())->toBe("line one\nline two")
        ->and($value->getMarkdown())->toBe("line one\nline two")
        ->and($value->getFlavor())->toBe('gfm-comment')
        ->and($value->getHtml())->toBe("<p>line one<br>\nline two</p>\n")
        ->and($value->toHtml())->toBe("<p>line one<br>\nline two</p>\n")
        ->and((string) $value)->toBe("<p>line one<br>\nline two</p>\n")
        ->and($value->serialize())->toBe("line one\nline two");
});

it('renders empty markdown as an empty string', function () {
    $value = new MarkdownData('', 'gfm');

    expect($value->getHtml())->toBe('')
        ->and((string) $value)->toBe('')
        ->and($value->serialize())->toBe('');
});

it('can encode markdown before rendering', function () {
    $value = new MarkdownData('<b>**bold**</b>', 'pre-encoded', encode: true);

    expect($value->getRaw())->toBe('<b>**bold**</b>')
        ->and($value->getHtml())->toBe("<p>&lt;b&gt;<strong>bold</strong>&lt;/b&gt;</p>\n");
});

it('can render inline-only markdown', function () {
    $value = new MarkdownData('**bold**', 'gfm', inlineOnly: true);

    expect($value->getHtml())->toBe('<strong>bold</strong>');
});

it('can sanitize rendered html', function () {
    HtmlSanitizers::extend('paragraphs-only', new HtmlSanitizer(
        (new HtmlSanitizerConfig)->allowElement('p')
    ));

    $value = new MarkdownData('<p onclick="bad()">Hi</p><h1>Heading</h1>', 'gfm', sanitizeHtml: true, htmlSanitizer: 'paragraphs-only');

    expect($value->getRaw())->toBe('<p onclick="bad()">Hi</p><h1>Heading</h1>')
        ->and($value->getHtml())->toBe("<p>Hi</p>\n");
});

it('parses element reference tags before rendering markdown', function () {
    Elements::shouldReceive('parseRefs')
        ->once()
        ->with('![Alt]({asset:1@2:url})')
        ->andReturn('![Alt](https://example.test/image.jpg)');

    $value = new MarkdownData('![Alt]({asset:1@2:url})', 'gfm');

    expect($value->getHtml())->toBe("<p><img src=\"https://example.test/image.jpg\" alt=\"Alt\" /></p>\n");
});
