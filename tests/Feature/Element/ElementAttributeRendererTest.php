<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementAttributeRenderer;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Twig\Markup;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->renderer = app(ElementAttributeRenderer::class);

    actingAs(User::findOne());
});

it('can be resolved as a singleton', function () {
    $renderer1 = app(ElementAttributeRenderer::class);
    $renderer2 = app(ElementAttributeRenderer::class);

    expect($renderer1)->toBe($renderer2);
});

it('renders id attribute', function () {
    Entry::factory()->create();
    $entry = EntryElement::findOne();

    expect($this->renderer->render($entry, 'id'))->toBe((string) $entry->id);
});

it('renders uid attribute', function () {
    Entry::factory()->create();
    $entry = EntryElement::findOne();

    expect($this->renderer->render($entry, 'uid'))->toBe($entry->uid);
});

it('renders slug attribute', function () {
    Entry::factory()->create();
    $entry = EntryElement::findOne();

    expect($this->renderer->render($entry, 'slug'))->toBe($entry->slug);
});

it('renders empty string for slug when draft has temp slug', function () {
    Entry::factory()->create();
    $entry = EntryElement::findOne();
    $entry->draftId = 1;
    $entry->slug = '__temp_'.time();

    expect($this->renderer->render($entry, 'slug'))->toBe('');
});

it('renders empty string for parent when no parent exists', function () {
    Entry::factory()->create();
    $entry = EntryElement::findOne();

    expect($this->renderer->render($entry, 'parent'))->toBe('');
});

it('renders empty string for ancestors when no ancestors exist', function () {
    Entry::factory()->create();
    $entry = EntryElement::findOne();

    expect($this->renderer->render($entry, 'ancestors'))->toBe('');
});

it('renders empty string for revisionNotes when no revision exists', function () {
    Entry::factory()->create();
    $entry = EntryElement::findOne();

    expect($this->renderer->render($entry, 'revisionNotes'))->toBe('');
});

it('renders empty string for revisionCreator when no revision exists', function () {
    Entry::factory()->create();
    $entry = EntryElement::findOne();

    expect($this->renderer->render($entry, 'revisionCreator'))->toBe('');
});

it('renders empty string for drafts when no drafts are eager-loaded', function () {
    Entry::factory()->create();
    $entry = EntryElement::findOne();

    expect($this->renderer->render($entry, 'drafts'))->toBe('');
});

it('renders status attribute', function () {
    Entry::factory()->create();
    $entry = EntryElement::findOne();

    $result = $this->renderer->render($entry, 'status');

    expect((string) $result)->toContain('<craft-badge');
});

it('renders empty string for generatedField when not found', function () {
    Entry::factory()->create();
    $entry = EntryElement::findOne();

    expect($this->renderer->render($entry, 'generatedField:nonexistent-uid'))->toBe('');
});

it('renders empty string for contentBlock when not found', function () {
    Entry::factory()->create();
    $entry = EntryElement::findOne();

    expect((string) $this->renderer->render($entry, 'contentBlock:nonexistent-uid'))->toBe('');
});

it('renders empty string for field when not a previewable field', function () {
    Entry::factory()->create();
    $entry = EntryElement::findOne();

    expect($this->renderer->render($entry, 'field:nonexistent-uid'))->toBe('');
});

it('renders empty string for fieldInstance when not found', function () {
    Entry::factory()->create();
    $entry = EntryElement::findOne();

    expect($this->renderer->render($entry, 'fieldInstance:nonexistent-uid'))->toBe('');
});

it('renders inline input by falling back to regular render for unknown attributes', function () {
    Entry::factory()->create();
    $entry = EntryElement::findOne();

    expect($this->renderer->renderInlineInput($entry, 'id'))->toBe((string) $entry->id);
});

it('renders inline input empty for field when not found', function () {
    Entry::factory()->create();
    $entry = EntryElement::findOne();

    $result = $this->renderer->renderInlineInput($entry, 'field:nonexistent-uid');

    expect($result)->toBe('');
});

it('returns null from getFieldFromAlternativeLayouts when no alternative layouts exist', function () {
    Entry::factory()->create();
    $entry = EntryElement::findOne();

    $result = $this->renderer->getFieldFromAlternativeLayouts($entry, 'nonexistent-uid');

    expect($result)->toBeNull();
});

it('renders plain values with attributeHtml', function () {
    expect($this->renderer->attributeHtml('<strong>Test</strong>'))->toBe('Test');
    expect($this->renderer->attributeHtml(new Markup('<em>Test</em>', 'UTF-8')))->toBe('<em>Test</em>');
});

it('renders empty string for non stringable objects with attributeHtml', function () {
    expect($this->renderer->attributeHtml(new stdClass))->toBe('');
});

it('renders direct link helpers', function () {
    $linkHtml = $this->renderer->linkAttributeHtml('https://example.test');
    $uriHtml = $this->renderer->uriAttributeHtml('path/to/page', 'https://example.test/path/to/page');

    expect($linkHtml)
        ->toContain('href="https://example.test"')
        ->toContain('target="_blank"')
        ->and($uriHtml)
        ->toContain('>path/to/page<')
        ->toContain('class="go"')
        ->toContain('href="https://example.test/path/to/page"');
});

it('renders the homepage uri as a labelled home icon', function () {
    Sites::getPrimarySite()->setBaseUrl('https://localhost/');
    Entry::factory()->create();
    $entry = EntryElement::findOne();
    $entry->uri = Element::HOMEPAGE_URI;

    expect($this->renderer->render($entry, 'uri'))
        ->toContain('<span class="sr-only">Homepage</span>')
        ->not->toContain('aria-label="Homepage"')
        ->toContain('class="go"')
        ->toContain('href="https://localhost"');
});

it('renders non-homepage uris as breakable text', function () {
    Sites::getPrimarySite()->setBaseUrl('https://localhost/');
    Entry::factory()->create();
    $entry = EntryElement::findOne();
    $entry->uri = 'foo/bar';

    expect($this->renderer->render($entry, 'uri'))
        ->toContain('>foo/<wbr>bar<')
        ->not->toContain('<svg');
});

it('announces that uri links open in a new window', function () {
    $uriHtml = $this->renderer->uriAttributeHtml('path/to/page', 'https://example.test/path/to/page');

    expect($uriHtml)
        ->toContain('target="_blank"')
        ->toContain('<span class="sr-only">Opens in a new window</span></a>');
});

it('announces that link attribute links open in a new window', function () {
    $linkHtml = $this->renderer->linkAttributeHtml('https://example.test');

    expect($linkHtml)
        ->toContain('target="_blank"')
        ->toContain('<span class="sr-only">View</span><span class="sr-only">Opens in a new window</span></a>')
        ->not->toContain('aria-label=');
});
