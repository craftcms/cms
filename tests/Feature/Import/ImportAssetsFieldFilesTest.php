<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Field\Assets as AssetsField;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\ImportFixtures;
use CraftCms\UrlValidator\UrlValidator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->import = app(Import::class);

    $this->diskRoot = storage_path('framework/testing/import-assets-field/'.bin2hex(random_bytes(4)));
    config()->set('filesystems.disks.import-assets-field-disk', [
        'driver' => 'local',
        'root' => $this->diskRoot,
    ]);

    $this->volume = Volume::factory()->create([
        'name' => 'Uploads',
        'handle' => 'uploads',
        'fs' => 'import-assets-field-disk',
        'fieldLayoutId' => FieldLayout::factory()->create(['type' => Asset::class])->id,
    ]);

    $this->rootFolder = Folders::getRootFolderByVolumeId($this->volume->id);

    $volumeSource = "volume:{$this->volume->uid}";

    Field::factory()->create([
        'name' => 'My Assets',
        'handle' => 'myAssets',
        'type' => AssetsField::class,
        'settings' => ['defaultUploadLocationSource' => $volumeSource],
    ]);

    Field::factory()->create([
        'name' => 'Restricted Assets',
        'handle' => 'restrictedAssets',
        'type' => AssetsField::class,
        'settings' => [
            'restrictLocation' => true,
            'restrictedLocationSource' => $volumeSource,
            'restrictedLocationSubpath' => 'restricted',
        ],
    ]);

    Fields::refreshFields();

    $blockAssetsField = Field::factory()->create([
        'name' => 'Block Assets',
        'handle' => 'blockAssets',
        'type' => AssetsField::class,
        'settings' => ['defaultUploadLocationSource' => $volumeSource],
    ]);
    $matrixField = ImportFixtures::matrixField('myMatrix', [ImportFixtures::blockEntryType('gallery', [$blockAssetsField], 'Gallery')], 'My Matrix');

    Fields::refreshFields();

    $seed = ImportFixtures::seedEntry([
        CustomField::make('myAssets'),
        CustomField::make('restrictedAssets'),
        CustomField::make($matrixField->handle),
    ], ['name' => 'With Assets', 'handle' => 'withAssets']);

    $this->section = $seed->section;
    $this->entryType = $seed->entryType;

    $this->importer = EntryImporter::create()
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null);

    $this->sourceDir = Path::temp('import-assets-field-'.bin2hex(random_bytes(4)));
    File::ensureDirectoryExists($this->sourceDir);

    $this->sourceFile = function (string $filename, string $contents = 'hello world'): string {
        $path = "$this->sourceDir/$filename";
        file_put_contents($path, $contents);

        return $path;
    };

    $this->importEntry = fn (string $title, array $fieldValues, ?EntryImporter $importer = null) => $this->import->importItem($importer ?? $this->importer, array_merge([
        'title' => $title,
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
        'matchCriteria' => ['title' => 'title'],
    ], $fieldValues));

    $this->entry = fn (string $title) => EntryElement::find()->title($title)->one();

    $this->assetsInFolder = fn (int $folderId) => Asset::find()->folderId($folderId)->all();
});

afterEach(function () {
    File::deleteDirectory($this->sourceDir);
    File::deleteDirectory($this->diskRoot);
});

it('creates an asset in the default upload location from a local file', function () {
    $source = ($this->sourceFile)('photo.txt');

    ($this->importEntry)('imported entry', ['myAssets' => $source]);

    $assets = ($this->entry)('imported entry')->getFieldValue('myAssets')->all();

    expect($assets)->toHaveCount(1)
        ->and($assets[0]->getFilename())->toBe('photo.txt')
        ->and($assets[0]->folderId)->toBe($this->rootFolder->id)
        ->and($assets[0]->getContents())->toBe('hello world')
        ->and(file_exists($source))->toBeTrue();
});

it('creates an asset in the restricted location', function () {
    ($this->importEntry)('imported entry', ['restrictedAssets' => [($this->sourceFile)('photo.txt')]]);

    $asset = ($this->entry)('imported entry')->getFieldValue('restrictedAssets')->one();

    expect($asset->getFolder()->path)->toBe('restricted/');
});

it('relates the existing asset when an incoming file matches one', function () {
    ($this->importEntry)('first entry', ['myAssets' => [($this->sourceFile)('photo.txt', 'original')]]);
    $existing = ($this->entry)('first entry')->getFieldValue('myAssets')->one();

    ($this->importEntry)('second entry', ['myAssets' => [($this->sourceFile)('photo.txt', 'incoming')]]);
    $related = ($this->entry)('second entry')->getFieldValue('myAssets')->one();

    expect($related->id)->toBe($existing->id)
        ->and($related->getContents())->toBe('original')
        ->and(($this->assetsInFolder)($this->rootFolder->id))->toHaveCount(1);
});

it('replaces the existing file when an incoming file matches one', function () {
    ($this->importEntry)('first entry', ['myAssets' => [($this->sourceFile)('photo.txt', 'original')]]);
    $existing = ($this->entry)('first entry')->getFieldValue('myAssets')->one();

    $importer = (clone $this->importer)->fieldSettings(['myAssets' => ['fileConflict' => 'replace']]);
    ($this->importEntry)('second entry', ['myAssets' => [($this->sourceFile)('photo.txt', 'incoming')]], $importer);
    $related = ($this->entry)('second entry')->getFieldValue('myAssets')->one();

    expect($related->id)->toBe($existing->id)
        ->and($related->getFilename())->toBe('photo.txt')
        ->and($related->getContents())->toBe('incoming')
        ->and(($this->assetsInFolder)($this->rootFolder->id))->toHaveCount(1);
});

it('leaves the existing file alone when the entry it was meant to be replaced for doesn’t save', function () {
    ($this->importEntry)('first entry', ['myAssets' => [($this->sourceFile)('photo.txt', 'original')]]);
    $existing = ($this->entry)('first entry')->getFieldValue('myAssets')->one();

    $importer = (clone $this->importer)->fieldSettings(['myAssets' => ['fileConflict' => 'replace']]);

    expect(fn () => ($this->importEntry)('second entry', [
        'postDate' => '2020-06-15 12:00:00',
        'expiryDate' => '2020-01-01 12:00:00',
        'myAssets' => [($this->sourceFile)('photo.txt', 'incoming')],
    ], $importer))
        ->toThrow(InvalidElementException::class)
        ->and(Asset::find()->id($existing->id)->one()->getContents())->toBe('original');
});

it('doesn’t resave an entry when its incoming file is already the related asset', function () {
    $source = ($this->sourceFile)('photo.txt');
    ($this->importEntry)('imported entry', ['myAssets' => [$source]]);

    $saveCount = 0;
    Event::listen(ElementSaving::class, function () use (&$saveCount) {
        $saveCount++;
    });

    ($this->importEntry)('imported entry', ['myAssets' => [$source]]);

    expect($saveCount)->toBe(0)
        ->and(($this->entry)('imported entry')->getFieldValue('myAssets')->count())->toBe(1);
});

it('creates a new asset when an incoming file matches one and that’s what the setting says', function () {
    ($this->importEntry)('first entry', ['myAssets' => [($this->sourceFile)('photo.txt', 'original')]]);
    $existing = ($this->entry)('first entry')->getFieldValue('myAssets')->one();

    $importer = (clone $this->importer)->fieldSettings(['myAssets' => ['fileConflict' => 'createNew']]);
    ($this->importEntry)('second entry', ['myAssets' => [($this->sourceFile)('photo.txt', 'incoming')]], $importer);
    $related = ($this->entry)('second entry')->getFieldValue('myAssets')->one();

    expect($related->id)->not->toBe($existing->id)
        ->and($related->getFilename())->not->toBe('photo.txt')
        ->and($related->getContents())->toBe('incoming')
        ->and(Asset::find()->id($existing->id)->one()->getContents())->toBe('original');
});

it('creates an asset from a URL', function () {
    Http::fake(['example.com/*' => Http::response('remote contents', 200, ['Content-Type' => 'text/plain'])]);

    $importer = new class extends EntryImporter
    {
        public static function urlValidator(?callable $resolver = null): UrlValidator
        {
            return parent::urlValidator(fn () => ['93.184.216.34']);
        }
    };
    $importer->site(Sites::getPrimarySite()->handle)->transformer(null);

    ($this->importEntry)('imported entry', ['myAssets' => ['https://example.com/files/remote.txt?v=2']], $importer);

    $asset = ($this->entry)('imported entry')->getFieldValue('myAssets')->one();

    expect($asset->getFilename())->toBe('remote.txt')
        ->and($asset->getContents())->toBe('remote contents');
});

it('skips a URL that redirects instead of saving the redirect as the file', function () {
    ImportLog::spy();
    Http::fake(['example.com/*' => Http::response('', 302, ['Location' => 'https://example.com/elsewhere.txt'])]);

    $importer = new class extends EntryImporter
    {
        public static function urlValidator(?callable $resolver = null): UrlValidator
        {
            return parent::urlValidator(fn () => ['93.184.216.34']);
        }
    };
    $importer->site(Sites::getPrimarySite()->handle)->transformer(null);

    ($this->importEntry)('imported entry', ['myAssets' => ['https://example.com/files/remote.txt']], $importer);

    expect(($this->entry)('imported entry')->getFieldValue('myAssets')->count())->toBe(0);
    ImportLog::shouldHaveReceived('warning')->withArgs(fn (string $message) => str_contains($message, 'remote.txt'));
});

it('takes the extension from the content type for a URL without one', function () {
    Http::fake(['example.com/*' => Http::response('remote contents', 200, ['Content-Type' => 'text/plain; charset=utf-8'])]);

    $importer = new class extends EntryImporter
    {
        public static function urlValidator(?callable $resolver = null): UrlValidator
        {
            return parent::urlValidator(fn () => ['93.184.216.34']);
        }
    };
    $importer->site(Sites::getPrimarySite()->handle)->transformer(null);

    ($this->importEntry)('imported entry', ['myAssets' => ['https://example.com/files/remote']], $importer);

    $asset = ($this->entry)('imported entry')->getFieldValue('myAssets')->one();

    expect($asset->getFilename())->toBe('remote.txt')
        ->and($asset->getContents())->toBe('remote contents');
});

it('keeps the incoming order of asset IDs and files', function () {
    ($this->importEntry)('first entry', ['myAssets' => [($this->sourceFile)('first.txt')]]);
    $existing = ($this->entry)('first entry')->getFieldValue('myAssets')->one();

    ($this->importEntry)('second entry', ['myAssets' => [($this->sourceFile)('second.txt'), (string) $existing->id]]);

    $filenames = array_map(
        fn (Asset $asset) => $asset->getFilename(),
        ($this->entry)('second entry')->getFieldValue('myAssets')->all(),
    );

    expect($filenames)->toBe(['second.txt', 'first.txt']);
});

it('adds an incoming file to an existing entry whose other values haven’t changed', function () {
    ($this->importEntry)('imported entry', ['myAssets' => [($this->sourceFile)('first.txt')]]);
    $first = ($this->entry)('imported entry')->getFieldValue('myAssets')->one();

    ($this->importEntry)('imported entry', ['myAssets' => [(string) $first->id, ($this->sourceFile)('second.txt')]]);

    expect(($this->entry)('imported entry')->getFieldValue('myAssets')->count())->toBe(2);
});

it('skips a disallowed file and still imports the entry', function () {
    ImportLog::spy();

    ($this->importEntry)('imported entry', ['myAssets' => [($this->sourceFile)('script.exe'), ($this->sourceFile)('photo.txt')]]);

    $assets = ($this->entry)('imported entry')->getFieldValue('myAssets')->all();

    expect($assets)->toHaveCount(1)
        ->and($assets[0]->getFilename())->toBe('photo.txt');
    ImportLog::shouldHaveReceived('warning')->withArgs(fn (string $message) => str_contains($message, 'script.exe'));
});

it('creates assets for an assets field inside a matrix entry using the entry type’s setting', function () {
    ($this->importEntry)('first entry', ['myAssets' => [($this->sourceFile)('photo.txt', 'original')]]);
    $existing = ($this->entry)('first entry')->getFieldValue('myAssets')->one();

    $importer = (clone $this->importer)->fieldSettings([
        'myMatrix' => ['gallery' => ['fields' => ['blockAssets' => ['fileConflict' => 'createNew']]]],
    ]);

    ($this->importEntry)('second entry', ['myMatrix' => [
        ['type' => 'gallery', 'title' => 'block 1', 'fields' => ['blockAssets' => [($this->sourceFile)('photo.txt', 'incoming')]]],
    ]], $importer);

    $block = ($this->entry)('second entry')->getFieldValue('myMatrix')->one();
    $asset = $block->getFieldValue('blockAssets')->one();

    expect($asset)->not->toBeNull()
        ->and($asset->id)->not->toBe($existing->id)
        ->and($asset->getContents())->toBe('incoming');
});

it('keeps a matrix entry’s relations when its relation field comes in empty', function () {
    $block = fn (?array $blockAssets) => ['myMatrix' => [
        ['type' => 'gallery', 'title' => 'block 1', 'matchCriteria' => ['title' => 'title'], 'fields' => ['blockAssets' => $blockAssets]],
    ]];
    $importer = (clone $this->importer)->matchCriteria(['title' => 'title']);

    ($this->importEntry)('imported entry', $block([($this->sourceFile)('photo.txt')]), $importer);
    ($this->importEntry)('imported entry', $block(null), $importer);

    $matrixEntry = ($this->entry)('imported entry')->getFieldValue('myMatrix')->one();

    expect($matrixEntry->getFieldValue('blockAssets')->count())->toBe(1);
});
