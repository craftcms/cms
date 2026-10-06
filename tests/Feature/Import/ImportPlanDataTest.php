<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

use function Pest\Laravel\actingAs;

it('keeps JSON-encoded steps as a list of importers with uids, skipping any that can\'t be built', function () {
    $importPlan = new ImportPlanData(['steps' => json_encode([
        ['type' => EntryImporter::class, 'source' => 'first.json'],
        ['type' => 'Not\\An\\Importer', 'source' => 'missing.json'],
        ['type' => EntryImporter::class, 'source' => 'second.json'],
    ])]);

    expect($importPlan->steps)->toHaveCount(2)
        ->and(array_is_list($importPlan->steps))->toBeTrue()
        ->and(array_map(fn ($step) => $step->source, $importPlan->steps))->toBe(['first.json', 'second.json'])
        ->and(array_filter(array_map(fn ($step) => $step->uid, $importPlan->steps)))->toHaveCount(2);
});

it('logs a step whose importer can\'t be built, and drops it', function () {
    ImportLog::spy();

    $importPlan = new ImportPlanData(['steps' => json_encode([
        ['type' => EntryImporter::class, 'source' => 'entries.json', 'settings' => ['site' => 'no-such-site']],
    ])]);

    expect($importPlan->steps)->toBeEmpty();
    ImportLog::shouldHaveReceived('warning')->once();
});

it('drops a step whose type isn\'t an importer without logging it', function () {
    ImportLog::spy();

    $importPlan = new ImportPlanData(['steps' => json_encode([
        ['type' => 'Not\\An\\Importer', 'source' => 'entries.json'],
    ])]);

    expect($importPlan->steps)->toBeEmpty();
    ImportLog::shouldNotHaveReceived('warning');
});

it('gives a user who can view import plans the edit URL of an editable plan', function () {
    actingAs(UserModel::factory()->withPermissions(['accessCp', 'viewImportPlans'])->createElement());

    $importPlan = new ImportPlanData(['handle' => 'myImport', 'editable' => true]);

    expect($importPlan->getCpEditUrl())->toBe(Url::cpUrl('import/myImport'));
});

it('gives a file-based plan no edit URL, even for an admin', function () {
    actingAs(User::find()->admin()->one());

    $importPlan = new ImportPlanData(['handle' => 'fileBased', 'editable' => false]);

    expect($importPlan->getCpEditUrl())->toBeNull();
});
