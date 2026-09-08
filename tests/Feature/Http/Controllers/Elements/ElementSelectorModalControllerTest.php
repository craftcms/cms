<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Elements\ElementSelectorModalController;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Tests\TestClasses\Field\ModeThumbnailField;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

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

it('uses fit for modal thumbnail presence and list rendering', function (string $supportedMode, bool $hasThumb) {
    $entry = EntryModel::factory()->withField('thumbnail', ModeThumbnailField::class, value: $supportedMode)
        ->createElementWithFields()->element;
    $layout = $entry->getFieldLayout();
    $layout->thumbFieldKey = 'layoutElement:'.$layout->getCustomFieldElements()[0]->uid;
    expect(Fields::saveLayout($layout))->toBeTrue();

    $response = ($this->postBody)()->assertOk();
    expect($response->json('props.data.0.elementInfo.hasThumb'))->toBe($hasThumb);
    $title = $response->json('props.data.0.title');
    if ($hasThumb) {
        expect($title)->toContainTag('craft-thumbnail', ['mode' => 'fit', 'sizes' => 'calc(30rem/16)']);
    } else {
        expect($title)->not->toContainTag('craft-thumbnail');
    }
})->with(['fit provider' => ['fit', true], 'crop-only provider' => ['crop', false]]);

it('narrows the sources to the ones the opener allows', function (?string $source) {
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

    UserModel::factory()->createElement(['active' => false, 'pending' => false]);
    $props = ($this->postBody)(['elementType' => User::class, 'sources' => ['inactive'], 'source' => $source])->json('props');
    expect($props['source']['key'])->toBe('inactive')
        ->and($props['pagination']['total'])->toBe(1)->and($props['data'])->toHaveCount(1);
})->with(['absent' => null, 'invalid' => 'missing']);

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
