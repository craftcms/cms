<?php

use craft\helpers\Cp;
use craft\markdown\GithubMarkdown;
use craft\markdown\Markdown;
use craft\markdown\MarkdownExtra;
use CraftCms\Cms\Support\Facades\Deprecator;
use yii\helpers\Markdown as MarkdownHelper;

beforeEach(function() {
    app()->forgetScopedInstances();
});

it('renders markdown through the yii helper flavors', function() {
    expect(MarkdownHelper::process('**bold**'))->toBe("<p><strong>bold</strong></p>\n")
        ->and(MarkdownHelper::processParagraph('**bold**', 'gfm'))->toBe('<strong>bold</strong>');
});

it('preserves gfm-comment line break behavior', function() {
    expect(MarkdownHelper::process("line one\nline two", 'gfm-comment'))
        ->toBe("<p>line one<br>\nline two</p>\n");
});

it('forwards cp markdown parsing to the source-side implementation', function() {
    expect(Cp::parseMarkdown('**bold**'))->toBe("<p><strong>bold</strong></p>\n");
});

it('uses commonmark unsafe link handling by default in legacy parser classes', function() {
    $parser = new Markdown();

    expect($parser->parse('[test](javascript:alert(1))'))
        ->toBe("<p><a>test</a></p>\n");
});

it('allows unsafe links when the legacy toggle is enabled', function() {
    $parser = new Markdown();
    $parser->parseJavaScriptLinks = true;

    expect($parser->parse('[test](javascript:alert(1))'))
        ->toBe("<p><a href=\"javascript:alert(1)\">test</a></p>\n");
});

it('honors the github parser newline toggle', function() {
    $parser = new GithubMarkdown();
    $parser->enableNewlines = true;

    expect($parser->parse("line one\nline two"))
        ->toBe("<p>line one<br>\nline two</p>\n");
});

it('logs that the legacy html5 toggle is ignored', function() {
    $parser = new Markdown();
    $parser->html5 = false;

    $parser->parse("line one  \nline two");

    $logs = array_values(Deprecator::getRequestLogs());

    expect($logs)->toHaveCount(1)
        ->and($logs[0]->message)->toContain('HTML5 output is always used');
});

it('logs that the legacy codeAttributesOnPre toggle is ignored', function() {
    $parser = new MarkdownExtra();
    $parser->codeAttributesOnPre = true;

    $parser->parse("``` {.foo}\nbar\n```");

    $logs = array_values(Deprecator::getRequestLogs());

    expect($logs)->toHaveCount(1)
        ->and($logs[0]->message)->toContain('Code block attributes use the CommonMark defaults');
});
