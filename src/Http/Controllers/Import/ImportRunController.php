<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Import;

use craft\helpers\Cp;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Import\Data\ImportRun;
use CraftCms\Cms\Import\Import;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

class ImportRunController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        GeneralConfig $generalConfig,
        private readonly Import $importService,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(): View
    {
        return view('craftcms::import.runs.index', [
            'readOnly' => $this->readOnly,
            'runs' => $this->importService->getImportRuns(),
        ]);
    }

    public function create(Request $request): CpScreenResponse
    {
        $old = $request->session()?->get('run');
        if (! empty($old)) {
            $run = new ImportRun($old);
        } else {
            $run = new ImportRun;
        }

        return $this->cpScreenResponse($run);
    }

    public function edit(Request $request, ?ImportRun $run = null, ?string $handle = null): CpScreenResponse
    {
        $handle ??= $run->handle ?? $request->input('handle');

        if (is_null($handle)) {
            return $this->create($request);
        }

        abort_if(is_null($found = $this->importService->getImportRunByHandle($handle)), 404, 'Import run not found');

        $old = $request->session()?->get('run');
        if (! empty($old)) {
            $run = new ImportRun($old);
        }

        if ($run === null) {
            $run = $found;
        }

        return $this->cpScreenResponse($run);
    }

    public function store(Request $request): Response
    {
        $runUid = $request->input('uid');

        if ($runUid) {
            abort_if(is_null($run = $this->importService->getImportRunByUid($runUid)), 400, "Invalid run UID: $runUid");
        } else {
            $run = new ImportRun;
        }

        $run->name = $request->input('name', $run->name);
        $run->handle = $request->input('handle', $run->handle);
        $run->description = $request->input('description', $run->description);
        $run->steps = $request->input('steps', $run->steps);

        if (! $this->importService->saveRun($run)) {
            return $this->asModelFailure($run, t('Couldn’t save import run.'), 'run');
        }

        return $this->asModelSuccess(
            $run,
            t('Import run saved.'),
            'run',
        );
    }

    public function destroy(Request $request): Response
    {
        $uid = $request->input('uid');

        if (! $uid) {
            throw ValidationException::withMessages([
                'id' => t('uid is required.'),
            ]);
        }

        $run = $this->importService->getImportRunByUid($uid);

        abort_if(is_null($run), 404, "Invalid import run UID: $uid");

        $this->importService->deleteRun($run);

        return $this->asSuccess(t('“{name}” deleted.', [
            'name' => $run->name,
        ]));
    }

    public function run(Request $request): Response
    {
        $uid = $request->input('uid');

        abort_if(is_null($uid), 400, 'Import run uid is required.');
        abort_if(is_null($run = $this->importService->getImportRunByUid($uid)), 400, 'Import run not found.');

        try {
            /** @phpstan-ignore-next-line */
            $this->importService->dispatchImport($run);
        } catch (Throwable $e) {
            Log::warning("Import run failed: {$e->getMessage()}");

            return $this->asFailure(t('Import could not be started.'));
        }

        return $this->asSuccess(t('Import started'));
    }

    private function cpScreenResponse(ImportRun $run): CpScreenResponse
    {
        $currentUser = auth('craft')->user();

        return new CpScreenResponse()
            ->title(! isset($run->uid) ? t('Create a new import run') : t('Edit {name} import run', ['name' => $run->name]))
            ->addCrumb(t('Import'), 'import')
            ->addCrumb(t('Runs'), 'import/runs')
            ->contentTemplate('import/runs/_edit.twig', [
                'run' => $run,
                'configs' => $this->importService->getAllConfigs()
                    ->map(fn ($config) => [
                        'label' => $config->name,
                        'value' => $config->editable ? $config->uid : $config->handle,
                        'data' => ['editable' => $config->editable],
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
                        $response->noticeHtml(Cp::readOnlyNoticeHtml());
                    }
                },
            );
    }
}
