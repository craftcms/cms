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

use function CraftCms\Cms\cp_url;
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
        'fs' => 'disk:edit-asset-test',
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
        );
});

it('compiles the field layout into a form payload', function () {
    get($this->asset->getCpEditUrl())
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('form.nodes')
            ->where('form.values.title', 'Current Title')
        );
});

it('renders the filename as a sidebar meta field', function () {
    get($this->asset->getCpEditUrl())
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sidebarForm.values.newFilename', 'current-file.png')
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

    expect($asset->formErrors())
        ->not->toHaveKey('newLocation')
        ->toHaveKey('newFilename');
});

it('rejects an id that doesn’t resolve to an asset', function () {
    get(cp_url('assets/edit/999999999-nope'))->assertBadRequest();
});
