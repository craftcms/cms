<?php

use craft\helpers\Cp;
use craft\markdown\GithubMarkdown;
use craft\markdown\Markdown;
use yii\helpers\Markdown as MarkdownHelper;

it('renders markdown through the yii helper flavors', function () {
    expect(MarkdownHelper::process('**bold**'))->toBe("<p><strong>bold</strong></p>\n")
        ->and(MarkdownHelper::processParagraph('**bold**', 'gfm'))->toBe("<strong>bold</strong>\n");
});

it('preserves gfm-comment line break behavior', function () {
    expect(MarkdownHelper::process("line one\nline two", 'gfm-comment'))
        ->toBe("<p>line one<br>\nline two</p>\n");
});

it('forwards cp markdown parsing to the source-side implementation', function () {
    expect(Cp::parseMarkdown('**bold**'))->toBe("<p><strong>bold</strong></p>\n");
});

it('uses commonmark unsafe link handling by default in legacy parser classes', function () {
    $parser = new Markdown();

    expect($parser->parse('[test](javascript:alert(1))'))
        ->toBe("<p><a>test</a></p>\n");
});

it('allows unsafe links when the legacy toggle is enabled', function () {
    $parser = new Markdown();
    $parser->parseJavaScriptLinks = true;

    expect($parser->parse('[test](javascript:alert(1))'))
        ->toBe("<p><a href=\"javascript:alert(1)\">test</a></p>\n");
});

it('honors the github parser newline toggle', function () {
    $parser = new GithubMarkdown();
    $parser->enableNewlines = true;

    expect($parser->parse("line one\nline two"))
        ->toBe("<p>line one<br>\nline two</p>\n");
});
