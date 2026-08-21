<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Html\ContentHtml;

describe('metadataHtml', function () {
    it('renders metadata rows and omits false values', function () {
        $html = app(ContentHtml::class)->metadataHtml([
            'One' => 'Value',
            'Two' => fn () => 'Computed',
            'Hidden' => fn () => false,
        ]);

        expect($html)->toContain('<dl class="cp-metadata-list">')
            ->and($html)->toContain('>One</dt>')
            ->and($html)->toContain('<dd>Value</dd>')
            ->and($html)->toContain('<dd>Computed</dd>')
            ->and($html)->not->toContain('Hidden');
    });
});

describe('readOnlyNoticeHtml', function () {
    it('renders the read-only settings notice', function () {
        $html = app(ContentHtml::class)->readOnlyNoticeHtml();

        expect($html)->toContain('content-notice')
            ->and($html)->toContain('content-notice-icon')
            ->and($html)->toContain('cp-icon');
    });
});

describe('parseMarkdown', function () {
    it('converts markdown to html', function () {
        $html = app(ContentHtml::class)->parseMarkdown('**bold**');

        expect($html)->toBe("<p><strong>bold</strong></p>\n");
    });

    it('relies on commonmark unsafe link handling by default', function () {
        $html = app(ContentHtml::class)->parseMarkdown('[test](javascript:alert(1))');

        expect($html)->toBe("<p><a>test</a></p>\n");
    });
});
