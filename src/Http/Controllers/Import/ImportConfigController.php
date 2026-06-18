<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Import;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Field\Contracts\ImportableElementContainerFieldInterface;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\ImportHelper;
use CraftCms\Cms\View\HtmlStack;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class ImportConfigController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        GeneralConfig $generalConfig,
        private HtmlStack $HtmlStack,
        private readonly Import $importService,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
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
        $validTypes = $this->importService->getAllImporterTypes();

        $old = $request->old() ?? $request->session()?->get('import');
        if (! empty($old)) {
            $type = $old['type'] ?? null;
            abort_unless(is_string($type) && in_array($type, $validTypes, strict: true), 400, 'Invalid importer type.');
            $import = new $type($old);
        } else {
            $type = $request->input('type');
            if ($type) {
                abort_unless(in_array($type, $validTypes, strict: true), 400, 'Invalid importer type.');
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
            'type' => ['required', 'string', Rule::in($this->importService->getAllImporterTypes())],
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
        $importConfigUid = $request->input('uid');

        $request->validate([
            'uid' => ['nullable', 'string', 'max:36'],
            'type' => [
                Rule::requiredIf(! $importConfigUid),
                'nullable',
                'string',
                Rule::in($this->importService->getAllImporterTypes()),
            ],
        ]);

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
        if (property_exists($import, 'site')) {
            $import->site($request->input('settings.site', $import->site));
        }
        $import->className($request->has('settings.elementType') ? $request->input('settings.elementType') : $request->input('settings.className'));
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

    public function editFieldLayoutProvider(Request $request, ?BaseImporter $import = null, ?string $handle = null): CpScreenResponse
    {
        $handle ??= $import->handle ?? $request->input('handle');

        abort_if(is_null($handle), 404, 'Import config not found');
        abort_if(is_null($found = $this->importService->getConfigByHandle($handle)), 404, 'Import config not found');
        abort_if(! $found->isEditable(), 400, "This import config is not editable: $found->handle");

        if ($import === null) {
            $import = $found;
        }

        $currentUser = auth('craft')->user();

        $templateVars = [
            'readOnly' => $this->readOnly,
            'static' => ! $currentUser?->can('editImportConfigs'),
            'import' => $import,
            'availableFieldLayoutProviders' => $import->getAvailableFieldLayoutProviders(),
        ];

        return new CpScreenResponse()
            ->title(t('Edit Field Layout Provider', ['name' => $import->name]))
            ->addCrumb(t('Import'), 'import')
            ->addCrumb(t('Configs'), 'import/configs')
            ->addCrumb(t($import->name), 'import/configs/'.$import->handle)
            ->contentTemplate('import/configs/_field-layout-provider.twig', $templateVars)
            ->unless(
                $this->readOnly || ! $currentUser?->can('editImportConfigs'),
                callback: function (CpScreenResponse $response) {
                    $response
                        ->action('import/configs/saveFieldLayoutProvider')
                        ->redirectUrl('import/configs')
                        ->addAltAction(t('Save and continue editing'), [
                            'redirect' => 'import/configs/{handle}/field-layout-provider',
                            'shortcut' => true,
                            'retainScroll' => true,
                        ]);
                },
                default: function (CpScreenResponse $response) {
                    if ($this->readOnly) {
                        $response->noticeHtml(new ContentHtml()->readOnlyNoticeHtml());
                    }
                },
            );
    }

    public function storeFieldLayoutProvider(Request $request): Response
    {
        $importConfigUid = $request->input('uid');
        abort_if(empty($importConfigUid), 400, 'No import config UID provided');

        $request->validate([
            'uid' => ['string', 'max:36'],
        ]);

        abort_if(is_null($import = $this->importService->getConfigByUid($importConfigUid)), 400, "Invalid import config UID: $importConfigUid");

        $request->validate([
            'fieldLayoutUid' => ['nullable', 'string', 'max:36'],
        ]);

        if (property_exists($import, 'fieldLayoutUid')) {
            $import->fieldLayoutUid($request->input('fieldLayoutUid', $import->fieldLayoutUid));
        }

        if (! $this->importService->saveConfig($import)) {
            return $this->asModelFailure($import, t('Couldn’t save import config.'), 'import');
        }

        return $this->asModelSuccess(
            $import,
            t('Import config saved.'),
            'import',
        );
    }

    public function editMap(Request $request, ?BaseImporter $import = null, ?string $handle = null): CpScreenResponse
    {
        $handle ??= $import->handle ?? $request->input('handle');

        abort_if(is_null($handle), 404, 'Import config not found');
        abort_if(is_null($found = $this->importService->getConfigByHandle($handle)), 404, 'Import config not found');
        abort_if(! $found->isEditable(), 400, "This import config is not editable: $found->handle");

        if ($import === null) {
            $import = $found;
        }

        $currentUser = auth('craft')->user();

        $templateVars = [
            'readOnly' => $this->readOnly,
            'static' => ! $currentUser?->can('editImportConfigs'),
            'import' => $import,
            'destinationCols' => $import->getDestinationCols(),
            'sourceDataCols' => $import->getSourceDataCols(),
        ];

        return new CpScreenResponse()
            ->title(t('Edit map', ['name' => $import->name]))
            ->addCrumb(t('Import'), 'import')
            ->addCrumb(t('Configs'), 'import/configs')
            ->addCrumb(t($import->name), 'import/configs/'.$import->handle)
            ->contentTemplate('import/configs/_map.twig', $templateVars)
            ->unless(
                $this->readOnly || ! $currentUser?->can('editImportConfigs'),
                callback: function (CpScreenResponse $response) {
                    $response
                        ->action('import/configs/saveMap')
                        ->redirectUrl('import/configs')
                        ->addAltAction(t('Save and continue editing'), [
                            'redirect' => 'import/configs/{handle}/map',
                            'shortcut' => true,
                            'retainScroll' => true,
                        ]);
                },
                default: function (CpScreenResponse $response) {
                    if ($this->readOnly) {
                        $response->noticeHtml(new ContentHtml()->readOnlyNoticeHtml());
                    }
                },
            );
    }

    public function storeMap(Request $request): Response
    {
        $importConfigUid = $request->input('importUid');
        abort_if(empty($importConfigUid), 400, 'No import config UID provided');

        $request->validate([
            'importUid' => ['string', 'max:36'],
        ]);

        abort_if(is_null($import = $this->importService->getConfigByUid($importConfigUid)), 400, "Invalid import config UID: $importConfigUid");

        $request->validate([
            'fieldLayoutId' => ['nullable', 'integer'],
            'map' => [
                'required',
                'array',
                fn ($attribute, $value, Closure $fail, Validator $validator) => $import::validateMap($value, $attribute, $fail, $validator),
            ],
        ]);

        if (property_exists($import, 'fieldLayoutId')) {
            $import->fieldLayoutId($request->input('fieldLayoutId', $import->fieldLayoutId));
        }

        $import->map($request->input('map', $import->map));

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

    public function editNestedFieldMapping(Request $request): CpScreenResponse
    {
        [$fieldUid, $field, $importUid, $import] = $this->fieldImportUids($request);

        $fieldHandle = $request->input('fieldHandle');
        $ownerPrefix = $request->input('ownerPrefix');

        $cols = [];

        if ($field instanceof ImportableElementContainerFieldInterface) {
            $providers = $field->getFieldLayoutProviders();
            foreach ($providers as $provider) {
                $fieldLayout = $provider->getFieldLayout();
                $cols[$provider->getHandle()] = [
                    'provider' => $provider,
                    'destinationCols' => ImportHelper::getDestinationColsForFieldLayout($fieldLayout, $field, $provider, $ownerPrefix),
                ];
            }
        }

        $currentUser = auth('craft')->user();

        $templateVars = [
            'readOnly' => $this->readOnly,
            'static' => ! $currentUser?->can('editImportConfigs'),
            'import' => $import,
            'field' => $field,
            'destinationCols' => $cols,
            'sourceDataCols' => $import->getSourceDataCols(),
            'nested' => true,
            'fieldHandle' => $fieldHandle,
        ];

        return new CpScreenResponse()
            ->title(t('Edit map for {fieldName}', ['fieldName' => $field->name]))
//            ->addCrumb(t('Import'), 'import')
//            ->addCrumb(t('Configs'), 'import/configs')
//            ->addCrumb(t($import->name), 'import/configs/'.$import->handle)
            ->contentTemplate('import/configs/_map.twig', $templateVars)
            ->unless(
                $this->readOnly || ! $currentUser?->can('editImportConfigs'),
                callback: function (CpScreenResponse $response) {
                    $response
                        ->action('import/configs/saveNestedFieldMapping')
                        ->redirectUrl('import/configs/{handle}/map');
                },
                default: function (CpScreenResponse $response) {
                    if ($this->readOnly) {
                        $response->noticeHtml(new ContentHtml()->readOnlyNoticeHtml());
                    }
                },
            );
    }

    public function storeNestedFieldMapping(Request $request): Response
    {
        [$fieldUid, $field, $importUid, $import] = $this->fieldImportUids($request);

        $fieldHandle = $request->input('fieldHandle', $field->handle);

        // validate the map fragment;
        // if it errors, a toast notification will show with the error
        $request->validate([
            'fieldHandle' => ['required', 'string', 'max:255'],
            "map.$fieldHandle" => [
                'required',
                'array',
                fn ($attribute, $value, Closure $fail, Validator $validator) => $import::validateMap($value, $attribute, $fail, $validator, ['field' => $field]),
            ],
        ]);

        // todo: if it errors - return asJsonError() and show the errors in the slideout?

        $map = $request->input("map.$fieldHandle");

        // and return it
        return $this->asJsonSuccess(null, ['fieldHandle' => $fieldHandle, 'map' => $map]);
    }

    private function fieldImportUids(Request $request): array
    {
        $fieldUid = $request->input('fieldUid');
        abort_if(empty($fieldUid), 400, 'No field UID provided');

        $field = Fields::getFieldByUid($fieldUid);
        abort_if(is_null($field), 400, 'Invalid field UID.');

        $importUid = $request->input('importUid');
        abort_if(empty($importUid), 400, 'No import UID provided');

        $import = $this->importService->getConfigByUid($importUid);
        abort_if(is_null($import), 400, 'Invalid import UID.');

        return [$fieldUid, $field, $importUid, $import];
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
                        $response->noticeHtml(new ContentHtml()->readOnlyNoticeHtml());
                    }
                },
            );
    }

    // TODO: this might be deleted - currently only used for file-based config, to run it from the configs screen
    public function run(Request $request): Response
    {
        $handle = $request->input('handle');

        abort_if(is_null($handle), 400, 'Import config handle is required.');
        abort_if(is_null($config = $this->importService->getConfigByHandle($handle)), 400, 'Import config not found.');

        try {
            $this->importService->import($config);
        } catch (Throwable $e) {
            Log::warning("Import failed: {$e->getMessage()}");

            return $this->asFailure(t('Import could not be started.'));
        }

        return $this->asSuccess(t('Import started'));
    }
}
