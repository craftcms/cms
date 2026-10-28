<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Controllers\Elements\PasteableElementsController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $this->pasteable = fn (array $payload) => postJson(action(PasteableElementsController::class), [
        'context' => ElementSources::CONTEXT_FIELD,
        ...$payload,
    ])->assertOk()->json('elements');

    $this->entryIn = (fn (Section $section, EntryType $entryType, string $title): Entry => EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => $title]));
});

it('requires authentication', function () {
    auth()->logout();

    postJson(action(PasteableElementsController::class))->assertUnauthorized();
});

it('returns the copied entries a field’s sources list, in the order they were copied', function () {
    $allowed = Section::factory()->withEntryTypes($entryType = EntryType::factory()->create())->create();
    $other = Section::factory()->withEntryTypes($otherType = EntryType::factory()->create())->create();
    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();

    $first = ($this->entryIn)($allowed, $entryType, 'First');
    $second = ($this->entryIn)($allowed, $entryType, 'Second');
    $elsewhere = ($this->entryIn)($other, $otherType, 'Elsewhere');

    $elements = ($this->pasteable)([
        'elementType' => Entry::class,
        'sources' => ["section:$allowed->uid"],
        'elementIds' => [$second->id, $elsewhere->id, $first->id],
    ]);

    expect(collect($elements)->map(fn (array $element) => [$element['id'], $element['label']])->all())
        ->toBe([[$second->id, 'Second'], [$first->id, 'First']]);
});

it('leaves out saved drafts', function () {
    $section = Section::factory()->withEntryTypes($entryType = EntryType::factory()->create())->create();
    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();

    $entry = ($this->entryIn)($section, $entryType, 'Canonical');
    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), name: 'Working draft');

    $elements = ($this->pasteable)([
        'elementType' => Entry::class,
        'elementIds' => [$draft->id, $entry->id],
    ]);

    expect(collect($elements)->pluck('id')->all())->toBe([$entry->id]);
});

it('finds assets in a volume’s subfolders, which the selector can open', function () {
    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/pasteable-elements-test/test-disk'),
    ]);

    $volume = Volumes::getVolumeById(Volume::factory()->create(['fs' => 'test-disk'])->id);
    $folder = Folders::ensureFolderByFullPathAndVolume('nested/deeper', $volume);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'nested.txt',
        'kind' => 'text',
    ]);

    $elements = ($this->pasteable)([
        'elementType' => Asset::class,
        'sources' => ["volume:$volume->uid"],
        'elementIds' => [$asset->id],
    ]);

    expect(collect($elements)->pluck('id')->all())->toBe([$asset->id]);
});
