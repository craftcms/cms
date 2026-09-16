<?php

declare(strict_types=1);

use CraftCms\Cms\Import\Events\RegisterImporterTypes;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ModelImporter;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Support\ImportHelper;
use CraftCms\Cms\SystemMessage\Import\SystemMessageImporter;
use CraftCms\Cms\SystemMessage\Models\SystemMessage;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->import = app(Import::class);

    $this->importer = SystemMessageImporter::create();

    // as the CLI command and the Import job do, resolve the importer's own match criteria once
    $this->matchCriteria = ImportHelper::normalizeMatchCriteriaFromImporterConfig($this->importer);

    $this->modelData = [
        'key' => 'my_message',
        'language' => 'en',
        'subject' => 'my subject',
        'body' => 'my body',
        'fake' => 'foo', // this should be filtered out by the import
    ];
});

it('imports into a model that is not an element', function () {
    $this->import->importItem($this->importer, $this->modelData, $this->matchCriteria);

    $message = SystemMessage::where(['key' => 'my_message', 'language' => 'en'])->first();
    expect($message?->getAttribute('subject'))->toBe('my subject')
        ->and($message?->getAttribute('body'))->toBe('my body');
});

it('always saves a brand-new model', function () {
    $saveCount = 0;
    SystemMessage::saving(function () use (&$saveCount) {
        $saveCount++;
    });

    $this->import->importItem($this->importer, $this->modelData, $this->matchCriteria);

    expect($saveCount)->toBe(1);
});

it('does not save when re-importing identical attributes', function () {
    $this->import->importItem($this->importer, $this->modelData, $this->matchCriteria);

    $saveCount = 0;
    SystemMessage::saving(function () use (&$saveCount) {
        $saveCount++;
    });

    $this->import->importItem($this->importer, $this->modelData, $this->matchCriteria);

    expect($saveCount)->toBe(0);
});

it('saves when re-importing with a changed attribute', function () {
    $this->import->importItem($this->importer, $this->modelData, $this->matchCriteria);

    $saveCount = 0;
    SystemMessage::saving(function () use (&$saveCount) {
        $saveCount++;
    });

    $this->import->importItem($this->importer, [...$this->modelData, 'body' => 'updated body'], $this->matchCriteria);

    expect($saveCount)->toBe(1);

    $message = SystemMessage::where(['key' => 'my_message', 'language' => 'en'])->first();
    expect($message?->getAttribute('body'))->toBe('updated body');
});

it('resolves the importer registered for a model', function () {
    expect($this->import->getModelImporterTypeFor(SystemMessage::class))->toBe(SystemMessageImporter::class);
});

it('returns null for a model with no registered importer', function () {
    expect($this->import->getModelImporterTypeFor(User::class))->toBeNull();
});

it('resolves a plugin-registered model importer', function () {
    Event::listen(function (RegisterImporterTypes $event) {
        $event->importers[] = TestModelImporter::class;
    });

    expect($this->import->getModelImporterTypeFor(TestModel::class))->toBe(TestModelImporter::class);
});

class TestModel extends BaseModel
{
    #[Override]
    protected $table = 'testmodels';
}

class TestModelImporter extends ModelImporter
{
    public function __construct(?array $config = null)
    {
        parent::__construct($config);

        $this->className = TestModel::class;
    }

    #[Override]
    public static function modelClass(): string
    {
        return TestModel::class;
    }

    #[Override]
    public static function displayName(): string
    {
        return 'Test Models';
    }

    public static function create(): self
    {
        return new self;
    }
}
