<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\LinkTypes;
use CraftCms\Cms\Field\LinkTypes\Url;

it('preserves enabled link metadata', function (bool $enabled, string $suffix) {
    $field = new Link([
        'types' => ['url'],
        'showLabelField' => $enabled,
        'advancedFields' => $enabled ? ['urlSuffix', 'target', 'title', 'class', 'id', 'rel', 'ariaLabel', 'download'] : [],
    ]);
    $metadata = [
        'label' => 'Read & learn', 'urlSuffix' => $suffix, 'target' => '_blank',
        'title' => 'Link title', 'class' => 'button primary', 'id' => 'my-link',
        'rel' => 'nofollow noopener', 'ariaLabel' => 'Read more', 'download' => true, 'filename' => 'guide.pdf',
    ];
    $data = $field->normalizeValue([
        'type' => 'url', 'value' => ' https://example.com ', ...$metadata,
        'class' => 'button primary[]', 'id' => 'my[link]', 'rel' => 'nofollow noopener[]',
    ], null);
    $expected = ['value' => 'https://example.com', 'type' => 'url'];
    if ($enabled) {
        $expected += [...$metadata, 'urlSuffix' => str_starts_with($suffix, '#') ? $suffix : '?'.ltrim($suffix, '?')];
    }

    expect($data->serialize())->toBe($expected);
    expect($field->normalizeValue($data->serialize(), null)->serialize())->toBe($expected);
    $document = new DOMDocument;
    $document->loadHTML((string) $data->getLink());
    $anchor = $document->getElementsByTagName('a')->item(0);
    expect($anchor->getAttribute('href'))->toBe('https://example.com'.($expected['urlSuffix'] ?? ''));
    expect($anchor->textContent)->toBe($enabled ? 'Read & learn' : 'example.com');
    foreach (['target', 'title', 'class', 'id', 'rel'] as $attribute) {
        expect($anchor->getAttribute($attribute))->toBe($enabled ? $metadata[$attribute] : '');
    }
    expect($anchor->getAttribute('aria-label'))->toBe($enabled ? 'Read more' : '');
    expect($anchor->hasAttribute('download'))->toBe($enabled);
    expect($anchor->getAttribute('download'))->toBe($enabled ? 'guide.pdf' : '');
})->with([true, false])->with(['page=2', '?page=2', '#section']);

it('preserves scalar and structured link inputs', function (mixed $input, ?string $expected) {
    $field = new Link(['types' => ['url']]);

    expect($field->normalizeValue($input, null)?->getUrl())->toBe($expected);
})->with([
    ['https://example.com', 'https://example.com'],
    [['url' => ['value' => ' https://example.com ']], 'https://example.com'],
    [['type' => 'email', 'value' => 'user@example.com'], 'mailto:user@example.com'],
    [null, null],
    ['', null],
    [['value' => ' '], null],
]);

it('uses the current registry link types', function () {
    $registry = app(LinkTypes::class);
    $registry->register(RegistryLink::class);

    $first = Link::types();
    $registry->remove(RegistryLink::class);
    $second = Link::types();

    expect($first)->toHaveKey('registry', RegistryLink::class)
        ->and($second)->not()->toHaveKey('registry');
});

class RegistryLink extends Url
{
    #[Override]
    public static function id(): string
    {
        return 'registry';
    }
}
