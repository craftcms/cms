<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Import;

use craft\helpers\Cp;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\View\HtmlStack;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class ImportConfigController
{
    use RespondsWithFlash;

    private bool $readOnly;

    private ?string $cpTrigger;

    public function __construct(
        Request $request,
        GeneralConfig $generalConfig,
        private HtmlStack $HtmlStack,
        private readonly Import $importService,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
        $this->cpTrigger = $generalConfig->cpTrigger;
    }

    public function index(): View
    {
        return view('craftcms::import.configs.index', [
            'readOnly' => $this->readOnly,
            'editableImportConfigs' => $this->importService->getEditableConfigs(),
            'nonEditableImportConfigs' => $this->importService->getNonEditableConfigs(),
        ]);
    }

    public function create(Request $request): CpScreenResponse
    {
        $old = $request->old() ?? $request->session()?->get('import');
        if (! empty($old)) {
            $import = new ($old['type'])($old);
        } else {
            $type = $request->input('type');
            if ($type) {
                $import = new $type;
            } else {
                $import = null;
            }
        }

        return $this->cpScreenResponse($import);
    }

    public function renderSettings(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string'],
            'namespace' => ['nullable', 'string'],
        ]);

        $type = $request->input('type');
        $import = new $type;

        $html = template('import/configs/_edit', [
            'import' => $import,
            'namespace' => $request->input('namespace'),
        ]);

        return new JsonResponse([
            'settingsHtml' => $html,
            'headHtml' => $this->HtmlStack->headHtml(),
            'bodyHtml' => $this->HtmlStack->bodyHtml(),
        ]);
    }

    public function edit(Request $request, ?BaseImporter $import = null, ?string $handle = null): CpScreenResponse
    {
        $handle ??= $import->handle ?? $request->input('handle');

        if (is_null($handle)) {
            return $this->create($request);
        }

        abort_if(is_null($found = $this->importService->getConfigByHandle($handle)), 404, 'Import config not found');
        abort_if(! $found->isEditable(), 400, "This import config is not editable: $found->handle");

        if ($import === null) {
            $import = $found;
        }

        return $this->cpScreenResponse($import);
    }

    public function store(Request $request): Response
    {
        $request->validate([
            'uid' => ['nullable', 'string', 'max:36'],
        ]);

        $importConfigUid = $request->input('uid');

        if ($importConfigUid) {
            abort_if(is_null($import = $this->importService->getConfigByUid($importConfigUid)), 400, "Invalid import config UID: $importConfigUid");
        } else {
            $import = new ($request->input('type'));
        }

        $request->validate($import::getRules());

        $import->name($request->input('name', $import->name));
        $import->handle($request->input('handle', $import->handle));
        $import->description($request->input('description', $import->description));
        $import->file($request->input('settings.file', $import->file));
        $import->site($request->input('settings.site', $import->site));
        $import->className($request->input('settings.elementType'));
        $import->transformer($request->input('settings.transformer', $import->transformer));
        $import->map($request->input('settings.map', $import->map));
        $import->matchCriteria($request->input('settings.matchCriteria', $import->matchCriteria));

        if (! $this->importService->saveConfig($import)) {
            // Flash::fail(t('Couldn’t save import config.'));
            return $this->asModelFailure($import, t('Couldn’t save import config.'), 'import');
        }

        return $this->asModelSuccess(
            $import,
            t('Import config saved.'),
            'import',
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

        $config = $this->importService->getConfigByUid($uid);

        abort_if(is_null($config), 404, "Invalid import config UID: $uid");
        abort_if(! $config->editable, 400, "This import config is not editable, so it can’t be deleted via the Control Panel: $uid");

        $this->importService->deleteConfig($config);

        return $this->asSuccess(t('“{name}” deleted.', [
            'name' => $config->name,
        ]));
    }

    private function cpScreenResponse(?BaseImporter $import = null): CpScreenResponse
    {
        $currentUser = auth('craft')->user();

        $templateVars = [
            'readOnly' => $this->readOnly,
            'static' => ! $currentUser?->can('editImportConfigs'),
            'import' => $import,
        ];

        if ($import === null) {
            $templateVars['importerTypes'] = array_map(fn ($type) => [
                'label' => $type::displayName(),
                'value' => $type,
            ], $this->importService->getAllImporterTypes());
            array_unshift($templateVars['importerTypes'], ['label' => t('Please select'), 'value' => null]);
        }

        return new CpScreenResponse()
            ->title(! isset($import?->uid) ? t('Create a new import config') : t('Edit {name} import config', ['name' => $import->name]))
            ->addCrumb(t('Import'), 'import')
            ->addCrumb(t('Configs'), 'import/configs')
            ->contentTemplate('import/configs/_edit.twig', $templateVars)
            ->unless(
                $this->readOnly || ! $currentUser?->can('editImportConfigs'),
                callback: function (CpScreenResponse $response) use ($import) {
                    $response
                        ->action('import/configs/save')
                        ->redirectUrl('import/configs')
                        ->addAltAction(t('Save and continue editing'), [
                            'redirect' => 'import/configs/{handle}',
                            'shortcut' => true,
                            'retainScroll' => true,
                        ])
                        ->addAltAction(t('Delete'), [
                            'action' => 'import/configs/delete',
                            'redirect' => 'import/configs',
                            'destructive' => true,
                            'confirm' => t('Are you sure you want to delete “{name}”?', [
                                'name' => $import?->name,
                            ]),
                        ]);
                },
                default: function (CpScreenResponse $response) {
                    if ($this->readOnly) {
                        $response->noticeHtml(Cp::readOnlyNoticeHtml());
                    }
                },
            );
    }

    public function run(Request $request): Response
    {
        $handle = $request->input('handle');

        abort_if(is_null($handle), 400, 'Import config handle is required.');
        abort_if(is_null($config = $this->importService->getConfigByHandle($handle)), 400, 'Import config not found.');

        try {
            $file = $config->file;
            $filePath = BaseImporter::resolvedFilePath($file);

            // TEMP - start
            $allData = $this->importService->getData($filePath);
            $data = array_slice($allData, 0);
            $dataCount = count($data);

            for ($i = 0; $i < $dataCount; $i++) {
                $this->importService->importItem($config, $data[$i]);
            }
            // TEMP - end
        } catch (Throwable $e) {
            Log::warning("Import failed: {$e->getMessage()}");

            return $this->asFailure(t('Import could not be started.'));
        }

        return $this->asSuccess(t('Import started'));
    }
}
