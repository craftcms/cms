<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Email;
use CraftCms\Cms\Field\Link;

it('shows link previews as craft-link in cards and tables', function (string $fieldType, ?string $viewMode, ?string $wrapper) {
    $entry = new Entry(['viewMode' => $viewMode]);
    $html = match ($fieldType) {
        'link' => ($link = new Link(['types' => ['url']]))->getPreviewHtml(
            $link->normalizeValue('https://www.example.com/a/very/long/path', null),
            $entry,
        ),
        'email' => (new Email)->getPreviewHtml('someone@example.com', $entry),
    };

    $document = new DOMDocument;
    libxml_use_internal_errors(true);
    $document->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $anchor = $document->getElementsByTagName('a')->item(0);
    expect($anchor)->not->toBeNull();
    expect($anchor->parentNode instanceof DOMElement ? $anchor->parentNode->tagName : null)->toBe($wrapper);
})->with(['link', 'email'])->with([
    'cards' => ['cards', 'craft-link'],
    'table' => ['table', 'craft-link'],
    'thumbs' => ['thumbs', null],
    'no index' => [null, null],
]);
