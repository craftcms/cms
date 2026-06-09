<?php

declare(strict_types=1);

use CraftCms\Cms\Announcement\Models\Announcement;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ModelImporter;
use CraftCms\Cms\User\Models\User;

beforeEach(function () {
    $this->import = app(Import::class);

    $this->importer = ModelImporter::create()
        ->className(Announcement::class)
        ->matchCriteria(['userId' => 'userId', 'heading' => 'heading'])
        ->transformer(null);

    $this->newUser = User::factory()->createElement();

    $this->modelData = [
        'userId' => $this->newUser->id,
        'heading' => 'my heading',
        'body' => 'my body',
        'fake' => 'foo', // this should be filtered out by the import
    ];
});

it('imports into a model that is not an element', function () {
    $this->import->importItem($this->importer, $this->modelData);
    $announcement = Announcement::where(['userId' => $this->newUser->id, 'heading' => $this->modelData['heading']])->first();
    expect($announcement?->getAttribute('body'))?->toBe($this->modelData['body']);
});
