<?php

declare(strict_types=1);

use craft\models\AssetIndexData;
use craft\services\AssetIndexer;
use CraftCms\Cms\Asset\Enums\AssetIndexStatus;
use CraftCms\Cms\Asset\Models\AssetIndexData as AssetIndexDataModel;
use CraftCms\Cms\Asset\Models\AssetIndexingSession;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function() {
    Schema::dropIfExists('assetindexdata');
    Schema::create('assetindexdata', function(Blueprint $table) {
        $table->integer('id', true);
        $table->integer('sessionId');
        $table->integer('volumeId');
        $table->text('uri')->nullable();
        $table->unsignedBigInteger('size')->nullable();
        $table->dateTime('timestamp')->nullable();
        $table->boolean('isDir')->default(false);
        $table->integer('recordId')->nullable();
        $table->string('status')->default('pending');
        $table->dateTime('dateCreated');
        $table->dateTime('dateUpdated');
        $table->char('uid', 36);
    });

    $this->session = new AssetIndexingSession(['id' => 1]);
    $this->entry = AssetIndexDataModel::create([
        'sessionId' => 1,
        'volumeId' => 1,
        'uri' => 'photo.jpg',
    ]);
    $this->indexer = Craft::$app->getAssetIndexer();
});

it('returns computed legacy status flags', function() {
    $entry = $this->indexer->getNextIndexEntry($this->session);

    expect($this->indexer)->toBeInstanceOf(AssetIndexer::class)
        ->and($entry)->toBeInstanceOf(AssetIndexData::class)
        ->and($entry->inProgress)->toBeFalse()
        ->and($entry->completed)->toBeFalse()
        ->and($entry->isSkipped)->toBeFalse();
});

it('translates legacy status writes', function() {
    $this->indexer->updateIndexEntry($this->entry->id, ['inProgress' => true]);

    expect($this->entry->refresh()->status)->toBe(AssetIndexStatus::Processing);

    $this->indexer->updateIndexEntry($this->entry->id, [
        'completed' => true,
        'inProgress' => false,
        'recordId' => 42,
    ]);

    expect($this->entry->refresh()->status)->toBe(AssetIndexStatus::Indexed)
        ->and($this->entry->recordId)->toBe(42);

    $skippedEntry = AssetIndexDataModel::create([
        'sessionId' => 1,
        'volumeId' => 1,
        'uri' => 'document.exe',
    ]);

    $this->indexer->updateIndexEntry($skippedEntry->id, ['inProgress' => true]);
    $this->indexer->updateIndexEntry($skippedEntry->id, [
        'completed' => true,
        'inProgress' => false,
        'isSkipped' => true,
    ]);

    expect($skippedEntry->refresh()->status)->toBe(AssetIndexStatus::Skipped);
});

it('rejects contradictory legacy status writes', function() {
    $this->indexer->updateIndexEntry($this->entry->id, [
        'inProgress' => true,
        'completed' => true,
    ]);
})->throws(LogicException::class, 'Invalid legacy asset index status flags.');
