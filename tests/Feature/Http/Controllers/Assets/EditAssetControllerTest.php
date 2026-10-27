<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\Assets\AltField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\DomCrawler\Crawler;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::findOne());
    Queue::fake();

    config()->set('filesystems.disks.edit-asset-test', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/edit-asset-controller-test'),
    ]);

    $layout = FieldLayout::make(Asset::class)
        // The Title field is mandatory, so the layout supplies it itself.
        ->tab('Content', fn (FieldLayoutTab $tab) => $tab
            ->add(new AltField(['uid' => 'asset-alt'])));
    $config = $layout->getConfig();
    $config['tabs'][0]['uid'] = 'asset-content';
    $layout = FieldLayoutModel::factory()->create(['type' => Asset::class, 'config' => $config]);

    $this->volume = Volume::factory()->create([
        'fs' => 'edit-asset-test',
        'fieldLayoutId' => $layout->id,
    ]);
    $this->folder = VolumeFolderModel::factory()->create(['volumeId' => $this->volume->id]);

    $this->asset = AssetModel::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->folder->id,
        'filename' => 'current-file.png',
        'kind' => 'image',
    ]);

    // The title lives on `elements_sites`, not the `assets` row the factory
    // writes, so it's set through the element.
    $this->asset->title = 'Current Title';
    Elements::saveElement($this->asset);
});

it('renders the asset edit screen as an Inertia page', function () {
    get($this->asset->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('assets/Edit')
            ->where('elementId', $this->asset->id)
            ->where('canonicalId', $this->asset->id)
            ->where('elementType', Asset::class)
            ->where('siteId', $this->asset->siteId)
            ->where('volumeId', $this->volume->id)
            ->where('folderId', $this->folder->id)
            ->where('title', 'Current Title')
            ->where('readOnly', false)
            ->where('activityTimelineUrl', fn (?string $url) => is_string($url)
                && str_contains($url, 'elements/activity'))
        );
});

it('ends the breadcrumbs with an unlinked chip for the asset', function () {
    get($this->asset->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('crumbs', function (Collection $crumbs) {
                $chip = $crumbs->last()['html'] ?? '';

                return str_contains($chip, 'data-id="'.$this->asset->id.'"')
                    && ! str_contains($chip, '<a ');
            })
            ->etc()
        );
});

it('compiles the field layout into a UI payload', function () {
    get($this->asset->getCpEditUrl())
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('ui.nodes')
            ->where('ui.values.title', 'Current Title')
        );
});

it('renders the filename as a sidebar meta field', function () {
    get($this->asset->getCpEditUrl())
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sidebarUi.values.newFilename', 'current-file.png')
        );
});

it('posts to the generic element save action', function () {
    get($this->asset->getCpEditUrl())
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('saveUrl', fn (string $url): bool => str_contains($url, 'elements/save'))
        );
});

it('does not autosave, since assets have no drafts', function () {
    get($this->asset->getCpEditUrl())
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('canAutosave', false)
            ->where('draftId', null)
            ->where('isProvisionalDraft', false)
            ->where('contextMenu', null)
        );
});

it('offers the asset’s own actions in the action menu', function () {
    get($this->asset->getCpEditUrl())
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('actionMenu', function (Collection $items): bool {
                $types = $items->pluck('behavior.type')->all();

                return in_array('download', $types, true)
                    && in_array('replaceFile', $types, true);
            })
        );
});

it('re-keys rename errors onto the field that posts them', function () {
    $asset = Asset::find()->id($this->asset->id)->one();
    $asset->errors()->add('newLocation', '“exe” is not an allowed file extension.');

    expect($asset->uiErrors())
        ->not->toHaveKey('newLocation')
        ->toHaveKey('newFilename');
});

it('rejects an id that doesn’t resolve to an asset', function () {
    get(cp_url('assets/edit/999999999-nope'))->assertBadRequest();
});

it('shows the image preview’s placeholder over a gradient between the image’s edge colors', function () {
    AssetModel::whereKey($this->asset->id)->update(['analysis' => json_encode(['colors' => [
        'dominant' => '#3a6ea5',
        'grid' => [
            ['#ff0000', '#3a6ea5', '#0000ff'],
            ['#990000', '#3a6ea5', '#000099'],
        ],
    ]])]);

    get($this->asset->getCpEditUrl())
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('previewFragment.html', fn (string $html): bool => str_contains(
                $html,
                'background-color: transparent; background-image: linear-gradient(#00000040, #0000000d), linear-gradient(to right, color-mix(in srgb, #d40000 40%, transparent), color-mix(in srgb, #0000d4 40%, transparent))',
            ) && str_contains($html, 'placeholder="data:image/png;base64,'))
        );
});

it('leaves the preview background alone without a usable dominant color', function (?string $colors) {
    AssetModel::whereKey($this->asset->id)->update(['analysis' => $colors === null ? null : json_encode(['colors' => json_decode($colors, true)])]);

    get($this->asset->getCpEditUrl())
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('previewFragment.html', fn (string $html): bool => str_contains($html, 'thumb-container')
                && ! str_contains($html, 'style='))
        );
})->with([
    'not sampled yet' => [null],
    'inconclusive' => ['{"dominant":null,"grid":[]}'],
    'no grid' => ['{"dominant":"#3a6ea5","grid":[]}'],
    'not a hex color' => ['{"dominant":null,"grid":[["red;x"]]}'],
]);

it('shows the uploader as a plain chip in the metadata', function () {
    AssetModel::whereKey($this->asset->id)->update(['uploaderId' => User::findOne()->id]);

    get($this->asset->getCpEditUrl())
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('metadataHtml', fn (string $html): bool => new Crawler($html)
                ->filter('craft-chip')
                ->attr('appearance') === 'plain')
        );
});

it('leaves out the status, since assets have no statuses', function () {
    get($this->asset->getCpEditUrl())
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('statusLabelHtml', null)
            ->where('metadataHtml', fn (string $html): bool => ! str_contains($html, 'craft-badge')
                && ! str_contains($html, '>'.t('Status').'<'))
        );
});

describe('image analysis', function () {
    beforeEach(function () {
        AssetModel::whereKey($this->asset->id)->update(['analysis' => json_encode([
            'colors' => ['dominant' => '#c81e28', 'grid' => [['#c81e28']], 'palette' => ['#c81e28', '#1e50c8'], 'luminance' => [[[0.13, 0.13]]], 'weights' => [0.7, 0.3]],
            'metadata' => [
                'title' => 'Current Title',
                'description' => 'Boats at dusk',
                'creator' => 'Ada Park',
                'keywords' => ['harbor', 'boats'],
                'dateTaken' => '2026-05-01T14:03:22+02:00',
                'camera' => 'Canon EOS R5',
                'latitude' => 48.208333,
                'longitude' => 16.37,
                'city' => 'Vienna',
                'country' => 'Austria',
            ],
            'type' => 'photo',
        ])]);
        $this->asset = Asset::find()->id($this->asset->id)->one();
    });

    it('shows the image type, colors, and embedded metadata in the metadata', function () {
        get($this->asset->getCpEditUrl())
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('metadataHtml', function (string $html): bool {
                    $values = [];
                    new Crawler($html)->filter('.cp-metadata-list__item')->each(function (Crawler $item) use (&$values) {
                        $values[$item->filter('dt')->text()] = $item->filter('dd');
                    });
                    $colors = $values[t('Colors')]->filter('.cp-color-bar');
                    $lists = new Crawler($html)->filter('dl');

                    // Apart from the rest of the metadata
                    expect($lists->count())->toBe(2)
                        ->and(new Crawler($html)->filter('dl + hr + dl')->count())->toBe(1)
                        ->and($lists->eq(0)->filter('dt')->each(fn (Crawler $dt) => $dt->text()))->toContain(t('Folder'))->not->toContain(t('Image type'))
                        ->and($lists->eq(1)->filter('dt')->first()->text())->toBe(t('Image type'));

                    expect($values[t('Image type')]->text())->toBe('Photo')
                        // One bar, each color as wide as its share, named for assistive technology
                        ->and($colors->attr('role'))->toBe('img')
                        ->and($colors->attr('aria-label'))->toBe('Red 70%, Blue 30%')
                        ->and($colors->attr('title'))->toBe('Red, Blue')
                        ->and($colors->filter('span')->each(fn (Crawler $segment) => $segment->attr('style')))->toBe([
                            'background-color: #c81e28; flex-grow: 0.7;',
                            'background-color: #1e50c8; flex-grow: 0.3;',
                        ])
                        // The same as the asset’s title, so it’s left out
                        ->and($values)->not->toHaveKey(t('Embedded title'))
                        ->and($values[t('Creator')]->text())->toBe('Ada Park')
                        ->and($values[t('Camera')]->text())->toBe('Canon EOS R5')
                        // In the time it was taken, not converted to the system’s time zone
                        ->and($values[t('Date taken')]->text())->toContain('2:03')
                        ->and($values)->not->toHaveKey(t('Credit'))
                        // A map of where it was taken, labeled with its place name, and a link to it in Google Maps
                        // Drawn from OpenStreetMap’s tiles around it, hidden from assistive technology since the label says the same
                        ->and($values[t('Location')]->filter('.cp-photo-location__map')->attr('aria-hidden'))->toBe('true')
                        ->and($values[t('Location')]->filter('.cp-photo-location__tiles img')->count())->toBe(9)
                        ->and($values[t('Location')]->filter('.cp-photo-location__tiles img')->eq(4)->attr('src'))->toBe('https://tile.openstreetmap.org/15/17874/11362.png')
                        ->and($values[t('Location')]->filter('.cp-photo-location__tiles img')->eq(4)->attr('alt'))->toBe('')
                        ->and($values[t('Location')]->filter('iframe')->count())->toBe(0)
                        // Only credited under the map, since the place name is beside “Location”
                        ->and($values[t('Location')]->filter('.cp-photo-location__caption')->text())->toBe('© OpenStreetMap contributors '.t('Opens in a new window'))
                        ->and($values[t('Location')]->filter('.cp-photo-location__caption a')->attr('href'))->toBe('https://www.openstreetmap.org/copyright');

                    // The link is the item’s value, and the map is below it, in its own <dd>
                    $location = new Crawler($html)->filter('.cp-metadata-list__item')->reduce(fn (Crawler $item) => $item->filter('dt')->text() === t('Location'));
                    $link = $location->filter('dd')->eq(0)->filter('a');

                    expect($location->filter('dd')->count())->toBe(2)
                        ->and($location->filter('dd')->eq(0)->children()->first()->text())->toBe('Vienna, Austria')
                        ->and($link->text())->toBe(t('View in Google Maps').' '.t('Opens in a new window'))
                        ->and($link->attr('href'))->toBe('https://www.google.com/maps/search/?api=1&query=48.208333%2C16.370000')
                        ->and($link->attr('target'))->toBe('_blank')
                        // An icon shows it opens in a new window, hidden from assistive technology, which is told in words
                        ->and($link->filter('.cp-icon')->attr('aria-hidden'))->toBe('true')
                        ->and($location->filter('dd')->eq(1)->filter('.cp-photo-location')->count())->toBe(1);

                    return true;
                })
            );
    });

    it('offers the image type, colors, and embedded metadata as table columns', function () {
        expect(Asset::tableAttributes())->toHaveKeys(['imageType', 'colors', 'creator', 'credit', 'copyright', 'dateTaken', 'camera', 'embeddedKeywords'])
            // “Location” is where an image was taken now
            ->and(Asset::tableAttributes()['location']['label'])->toBe(t('Folder'))
            ->and($this->asset->getAttributeHtml('imageType'))->toBe('Photo')
            ->and(Asset::cardAttributes())->toHaveKeys(['imageType', 'colors', 'creator', 'credit', 'copyright', 'dateTaken', 'camera', 'embeddedKeywords'])
            // The same bar
            ->and(new Crawler($this->asset->getAttributeHtml('colors'))->filter('.cp-color-bar')->attr('aria-label'))->toBe('Red 70%, Blue 30%')
            ->and($this->asset->getAttributeHtml('creator'))->toBe('Ada Park')
            ->and($this->asset->getAttributeHtml('embeddedKeywords'))->toBe('harbor, boats')
            ->and($this->asset->getAttributeHtml('copyright'))->toBe('');
    });
});

it('tiles colors evenly when it isn’t known how much of the image each covers', function () {
    AssetModel::whereKey($this->asset->id)->update(['analysis' => json_encode([
        'colors' => ['dominant' => '#c81e28', 'grid' => [['#c81e28']], 'palette' => ['#c81e28', '#1e50c8'], 'luminance' => [[[0.13, 0.13]]]],
    ])]);
    $bar = new Crawler(Asset::find()->id($this->asset->id)->one()->getAttributeHtml('colors'))->filter('.cp-color-bar');

    expect($bar->attr('aria-label'))->toBe('Red, Blue')
        ->and($bar->filter('span')->each(fn (Crawler $segment) => $segment->attr('style')))->toBe([
            'background-color: #c81e28; flex-grow: 1;',
            'background-color: #1e50c8; flex-grow: 1;',
        ]);
});

it('names where an image was taken without repeating its city, state, or country', function (array $place, ?string $expected) {
    AssetModel::whereKey($this->asset->id)->update(['analysis' => json_encode(['metadata' => ['latitude' => 48.2, 'longitude' => 16.37, ...$place]])]);

    get($this->asset->getCpEditUrl())
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('metadataHtml', function (string $html) use ($expected): bool {
                $value = new Crawler($html)->filter('.cp-metadata-list__item')
                    ->reduce(fn (Crawler $item) => $item->filter('dt')->text() === t('Location'))
                    ->filter('dd')->eq(0);

                expect($value->children()->count())->toBe($expected !== null ? 2 : 1)
                    ->and($value->children()->first()->text())->toBe($expected ?? t('View in Google Maps').' '.t('Opens in a new window'));

                return true;
            })
        );
})->with([
    'all' => [['city' => 'Portland', 'state' => 'Oregon', 'country' => 'United States'], 'Portland, Oregon, United States'],
    'a city that’s its state' => [['city' => 'Vienna', 'state' => 'vienna', 'country' => 'Austria'], 'Vienna, Austria'],
    'just a country' => [['country' => 'Austria'], 'Austria'],
    'none' => [[], null],
]);

it('names each of an image’s colors once on hover, while still giving each its share', function () {
    AssetModel::whereKey($this->asset->id)->update(['analysis' => json_encode([
        'colors' => ['dominant' => '#c81e28', 'grid' => [['#c81e28']], 'palette' => ['#c81e28', '#1e50c8', '#e01818'], 'luminance' => [[[0.13, 0.13]]], 'weights' => [0.5, 0.3, 0.2]],
    ])]);
    $bar = new Crawler(Asset::find()->id($this->asset->id)->one()->getAttributeHtml('colors'))->filter('.cp-color-bar');

    expect($bar->attr('title'))->toBe('Red, Blue')
        ->and($bar->attr('aria-label'))->toBe('Red 50%, Blue 30%, Red 20%');
});
