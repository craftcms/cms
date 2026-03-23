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

        expect($html)->toContain('<dl class="meta read-only">')
            ->and($html)->toContain('<dt class="heading">One</dt>')
            ->and($html)->toContain('<dd class="value">Value</dd>')
            ->and($html)->toContain('<dd class="value">Computed</dd>')
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

        expect($html)->toContain('<strong>bold</strong>');
    });
});
