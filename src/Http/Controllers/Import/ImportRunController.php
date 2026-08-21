<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Import;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Import\Data\ImportRun as ImportRunData;
use CraftCms\Cms\Import\Exceptions\InvalidConfigException;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\ImportConfig;
use CraftCms\Cms\Import\ImportRun;
use CraftCms\Cms\Support\Facades\ImportLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

class ImportRunController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        private Request $request,
        GeneralConfig $generalConfig,
        private readonly ImportRun $importRunService,
        private readonly Import $importService,
        private readonly ImportConfig $importConfigService,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(): View
    {
        return view('craftcms::import.runs.index', [
            'readOnly' => $this->readOnly,
            'runs' => $this->importRunService->getImportRuns(),
        ]);
    }

    public function create(): CpScreenResponse
    {
        $old = $this->request->session()->get('run');
        if (! empty($old)) {
            $run = new ImportRunData($old);
        } else {
            $run = new ImportRunData;
        }

        return $this->cpScreenResponse($run);
    }

    public function edit(?ImportRunData $run = null, ?string $handle = null): CpScreenResponse
    {
        $handle ??= $run->handle ?? $this->request->input('handle');

        if (is_null($handle)) {
            return $this->create();
        }

        abort_if(is_null($found = $this->importRunService->getImportRunByHandle($handle)), 404, 'Import run not found');

        $old = $this->request->session()->get('run');
        if (! empty($old)) {
            $run = new ImportRunData($old);
        }

        if ($run === null) {
            $run = $found;
        }

        return $this->cpScreenResponse($run);
    }

    public function store(): Response
    {
        $runUid = $this->request->input('uid');

        if ($runUid) {
            abort_if(is_null($run = $this->importRunService->getImportRunByUid($runUid)), 400, "Invalid run UID: $runUid");
        } else {
            $run = new ImportRunData;
        }

        $run->name = $this->request->input('name', $run->name);
        $run->handle = $this->request->input('handle', $run->handle);
        $run->description = $this->request->input('description', $run->description);
        $run->steps = $this->request->input('steps', $run->steps);

        if (! $this->importRunService->saveRun($run)) {
            return $this->asModelFailure($run, t('Couldn’t save import run.'), 'run');
        }

        return $this->asModelSuccess(
            $run,
            t('Import run saved.'),
            'run',
        );
    }

    public function destroy(): Response
    {
        $uid = $this->request->input('uid');

        if (! $uid) {
            throw ValidationException::withMessages([
                'id' => t('uid is required.'),
            ]);
        }

        $run = $this->importRunService->getImportRunByUid($uid);

        abort_if(is_null($run), 404, "Invalid import run UID: $uid");

        $this->importRunService->deleteRun($run);

        return $this->asSuccess(t('“{name}” deleted.', [
            'name' => $run->name,
        ]));
    }

    public function run(): Response
    {
        $uid = $this->request->input('uid');

        abort_if(is_null($uid), 400, 'Import run uid is required.');
        abort_if(is_null($run = $this->importRunService->getImportRunByUid($uid)), 400, 'Import run not found.');

        try {
            $this->importService->dispatchImport($run);
        } catch (InvalidConfigException $e) {
            return $this->asFailure(t("Import config “{$e->config}” not found. Review “{$run->name}” run and try again."));
        } catch (Throwable $e) {
            ImportLog::warning("Import run failed: {$e->getMessage()}");

            return $this->asFailure(t('Import could not be started.'));
        }

        return $this->asSuccess(t('Import started'));
    }

    private function cpScreenResponse(ImportRunData $run): CpScreenResponse
    {
        $currentUser = $this->request->craftUser();

        return new CpScreenResponse()
            ->title(! isset($run->uid) ? t('Create a new import run') : t('Edit {name} import run', ['name' => $run->name]))
            ->addCrumb(t('Import'), 'import')
            ->addCrumb(t('Runs'), 'import/runs')
            ->contentTemplate('import/runs/_edit.twig', [
                'run' => $run,
                'configs' => $this->importConfigService->getAllConfigs()
                    ->map(fn ($config) => [
                        'label' => $config->name,
                        'value' => $config->isEditable() ? $config->uid : $config->handle,
                        'data' => ['editable' => $config->isEditable()],
                    ])
                    ->prepend(['label' => t('Please select'), 'value' => null])
                    ->all(),
                'readOnly' => $this->readOnly,
                'static' => ! $currentUser?->can('editImportRuns'),
            ])
            ->unless(
                $this->readOnly || ! $currentUser?->can('editImportRuns'),
                callback: function (CpScreenResponse $response) use ($run) {
                    $response
                        ->action('import/runs/save')
                        ->redirectUrl('import/runs')
                        ->addAltAction(t('Save and continue editing'), [
                            'redirect' => 'import/runs/{handle}',
                            'shortcut' => true,
                            'retainScroll' => true,
                        ])
                        ->addAltAction(t('Delete'), [
                            'action' => 'import/runs/delete',
                            'redirect' => 'import/runs',
                            'destructive' => true,
                            'confirm' => t('Are you sure you want to delete “{name}”?', [
                                'name' => $run->name,
                            ]),
                        ]);

                    if ($run->uid) {
                        $response->addAltAction(t('Start this run'), [
                            'action' => 'import/run',
                            'redirect' => 'import/runs',
                            'confirm' => t('Are you sure you want to start this import?'),
                        ]);
                    }
                },
                default: function (CpScreenResponse $response) {
                    if ($this->readOnly) {
                        $response->noticeHtml(new ContentHtml()->readOnlyNoticeHtml());
                    }
                },
            );
    }
}
