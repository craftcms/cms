<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\ImportPlans;
use CraftCms\Cms\Import\Jobs\ImportPipeline;
use CraftCms\Cms\SystemMessage\Import\SystemMessageImporter;
use Illuminate\Support\Facades\Bus;

use function Pest\Laravel\artisan;

beforeEach(function () {
    Bus::fake();

    $this->originalRoot = Aliases::get('@root');
    Aliases::set('@root', dirname(__DIR__, 3));

    $this->importPlan = new ImportPlanData(['editable' => true])
        ->name('Unlock Plan')
        ->handle('unlockPlan')
        ->steps([new SystemMessageImporter(['source' => 'tests/Fixtures/Import/system-messages.json'])]);

    expect(app(ImportPlans::class)->saveImportPlan($this->importPlan))->toBeTrue();
});

afterEach(function () {
    Aliases::set('@root', $this->originalRoot);
});

it('unlocks an import plan that is marked as running without running it', function () {
    $import = app(Import::class);
    $import->dispatchImport($this->importPlan);

    artisan('craft:import:unlock-plan', ['plan' => 'unlockPlan'])
        ->expectsOutputToContain('Import plan unlocked.')
        ->assertSuccessful();

    expect($import->isRunning($this->importPlan))->toBeFalse();
    Bus::assertDispatchedTimes(ImportPipeline::class, 1);
});

it('fails to unlock an import plan that doesn’t exist', function () {
    artisan('craft:import:unlock-plan', ['plan' => 'missingPlan'])->assertFailed();
});
