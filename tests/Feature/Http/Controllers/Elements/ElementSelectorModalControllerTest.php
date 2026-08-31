<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Controllers\Elements\ElementSelectorModalController;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $this->postBody = fn (array $payload = []) => postJson(
        action(ElementSelectorModalController::class),
        array_merge([
            'context' => ElementSources::CONTEXT_MODAL,
            'elementType' => Entry::class,
        ], $payload),
        ['Accept' => 'application/json'],
    );
});

it('serves index props alongside the html the legacy index still boots from', function () {
    $response = ($this->postBody)();

    $response->assertOk();
    // The HTML is still there — the modal hasn't stopped using it yet.
    expect($response->json('html'))->toBeString()->not->toBeEmpty();

    $props = $response->json('props');
    expect($props)->toBeArray();
    expect($props['elementType'])->toBe(Entry::class);
    expect($props['sources'])->toBeArray()->not->toBeEmpty();
});

it('resolves sources in the modal context, not the index context', function () {
    $props = ($this->postBody)()->json('props');

    expect($props['context'])->toBe(ElementSources::CONTEXT_MODAL);
});

it('narrows the sources to the ones the opener allows', function () {
    $sourceKeys = fn (array $payload) => collect(
        ($this->postBody)($payload)->json('props.sources')
    )
        ->where('type', '!=', ElementSources::TYPE_HEADING)
        ->pluck('key')
        ->filter()
        ->values()
        ->all();

    // Users rather than entries: they're the fixture type with more than one
    // source, so narrowing can actually be observed.
    $all = $sourceKeys(['elementType' => User::class]);
    expect($all)->toContain('admins', 'inactive');
    expect(count($all))->toBeGreaterThan(2);

    expect($sourceKeys([
        'elementType' => User::class,
        'sources' => ['admins', 'inactive'],
    ]))->toBe(['admins', 'inactive']);
});

// A click in the modal is a selection. A linked title would instead navigate
// the CP behind the modal to the element's edit screen, dropping the selection
// the opener was collecting — so nothing in the modal's payload links out.
//
// Users rather than entries throughout: they're the fixture type that actually
// has rows, and they support all three view modes.
describe('titles are not links', function () {
    // Without this the rest would pass for the wrong reason — an element with no
    // edit URL renders unlinked everywhere, modal or not.
    beforeEach(fn () => expect(User::findOne()->getCpEditUrl())->not->toBeNull());

    it('renders table titles as plain chips rather than links', function () {
        $titles = collect(($this->postBody)(['elementType' => User::class])->json('props.data'))
            ->pluck('title');

        expect($titles)->not->toBeEmpty();
        $titles->each(fn (string $title) => expect($title)
            ->toContain('craft-chip')
            ->not->toContain('CpLink'));
    });

    it('hands thumbs no url to navigate to', function () {
        $thumbs = collect(($this->postBody)([
            'elementType' => User::class,
            'viewMode' => 'thumbs',
        ])->json('props.data'));

        expect($thumbs)->not->toBeEmpty();
        $thumbs->each(fn (array $thumb) => expect($thumb['url'])->toBeNull());
    });
});
