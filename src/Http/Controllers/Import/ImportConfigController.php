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
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\ImportHelper;
use CraftCms\Cms\Support\Json;
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
        private Request $request,
        private GeneralConfig $generalConfig,
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

    public function create(): CpScreenResponse
    {
        $validTypes = $this->importService->getAllImporterTypes();

        $old = $this->request->old() ?? $this->request->session()->get('import');
        if (! empty($old)) {
            $type = $old['type'] ?? null;
            abort_unless(is_string($type) && in_array($type, $validTypes, strict: true), 400, 'Invalid importer type.');
            $import = new $type($old);
        } else {
            $type = $this->request->input('type');
            if ($type) {
                abort_unless(in_array($type, $validTypes, strict: true), 400, 'Invalid importer type.');
                $import = new $type;
            } else {
                $import = null;
            }
        }

        return $this->cpScreenResponse($import);
    }

    public function renderSettings(): JsonResponse
    {
        $this->request->validate([
            'type' => ['required', 'string', Rule::in($this->importService->getAllImporterTypes())],
            'namespace' => ['nullable', 'string'],
        ]);

        $type = $this->request->input('type');
        $import = new $type;

        $html = template('import/configs/_edit', [
            'import' => $import,
            'namespace' => $this->request->input('namespace'),
        ]);

        return new JsonResponse([
            'settingsHtml' => $html,
            'headHtml' => $this->HtmlStack->headHtml(),
            'bodyHtml' => $this->HtmlStack->bodyHtml(),
        ]);
    }

    public function edit(?BaseImporter $import = null, ?string $handle = null): CpScreenResponse
    {
        $handle ??= $import->handle ?? $this->request->input('handle');

        if (is_null($handle)) {
            return $this->create();
        }

        abort_if(is_null($found = $this->importService->getConfigByHandle($handle)), 404, 'Import config not found');
        abort_if(! $found->isEditable(), 400, "This import config is not editable: $found->handle");

        if ($import === null) {
            $import = $found;
        }

        return $this->cpScreenResponse($import);
    }

    public function store(): Response
    {
        $importConfigUid = $this->request->input('uid');

        $this->request->validate([
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
            $import = new ($this->request->input('type'));
        }

        $this->request->validate($import::getRules());

        $import->name($this->request->input('name', $import->name));
        $import->handle($this->request->input('handle', $import->handle));
        $import->description($this->request->input('description', $import->description));
        $import->file($this->request->input('settings.file', $import->file));
        if (property_exists($import, 'site')) {
            $import->site($this->request->input('settings.site', $import->site));
        }
        $import->className($this->request->has('settings.elementType') ? $this->request->input('settings.elementType') : $this->request->input('settings.className'));
        $import->transformer($this->request->input('settings.transformer', $import->transformer));
        $import->map($this->request->input('settings.map', $import->map));
        $import->matchCriteria($this->request->input('settings.matchCriteria', $import->matchCriteria));

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

    public function editFieldLayoutProvider(?ElementImporter $import = null, ?string $handle = null): CpScreenResponse
    {
        $handle ??= $import->handle ?? $this->request->input('handle');

        abort_if(is_null($handle), 404, 'Import config not found');
        abort_if(is_null($found = $this->importService->getConfigByHandle($handle)), 404, 'Import config not found');
        abort_if(! $found->isEditable(), 400, "This import config is not editable: $found->handle");

        if ($import === null) {
            /** @var ElementImporter $import */
            $import = $found;
        }

        $currentUser = $this->request->craftUser();

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

    public function storeFieldLayoutProvider(): Response
    {
        $importConfigUid = $this->request->input('uid');
        abort_if(empty($importConfigUid), 400, 'No import config UID provided');

        $this->request->validate([
            'uid' => ['string', 'max:36'],
        ]);

        abort_if(is_null($import = $this->importService->getConfigByUid($importConfigUid)), 400, "Invalid import config UID: $importConfigUid");

        $this->request->validate([
            'fieldLayoutUid' => ['nullable', 'string', 'max:36'],
        ]);

        /** @var ElementImporter $import */
        if (property_exists($import, 'fieldLayoutUid')) {
            $import->fieldLayoutUid($this->request->input('fieldLayoutUid', $import->fieldLayoutUid));
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

    public function editMap(?BaseImporter $import = null, ?string $handle = null): CpScreenResponse
    {
        $handle ??= $import->handle ?? $this->request->input('handle');

        abort_if(is_null($handle), 404, 'Import config not found');
        abort_if(is_null($found = $this->importService->getConfigByHandle($handle)), 404, 'Import config not found');
        abort_if(! $found->isEditable(), 400, "This import config is not editable: $found->handle");

        if ($import === null) {
            $import = $found;
        }

        $currentUser = $this->request->craftUser();

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

    public function storeMap(): Response
    {
        $importConfigUid = $this->request->input('importUid');
        abort_if(empty($importConfigUid), 400, 'No import config UID provided');

        $this->request->validate([
            'importUid' => ['string', 'max:36'],
        ]);

        abort_if(is_null($import = $this->importService->getConfigByUid($importConfigUid)), 400, "Invalid import config UID: $importConfigUid");

        $this->request->validate([
            'fieldLayoutId' => ['nullable', 'integer'],
            'map' => [
                'required',
                'array',
                fn ($attribute, $value, Closure $fail, Validator $validator) => $import::validateMap($value, $attribute, $fail, $validator),
            ],
        ]);

        if (property_exists($import, 'fieldLayoutId')) {
            /** @phpstan-ignore-next-line */
            $import->fieldLayoutId($this->request->input('fieldLayoutId', $import->fieldLayoutId));
        }

        $import->map($this->request->input('map', $import->map));

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

    public function editNestedFieldMapping(): CpScreenResponse
    {
        [$fieldUid, $field, $importUid, $import] = $this->fieldImportUids();

        $fieldHandle = $this->request->input('fieldHandle');
        $ownerPrefix = $this->request->input('ownerPrefix');
        $currentPartialMap = $this->request->input('currentMap');

        if (is_string($currentPartialMap)) {
            $currentPartialMap = Json::decodeIfJson($currentPartialMap);

            // if you added mapping via a slideout, closed that slideout by clicking "apply" and you then open that slideout again,
            // if the config we have in the hidden field is different to the one coming from the server,
            // use the one coming from the hidden field
            $fieldHandleArray = ImportHelper::bracketsToArray($fieldHandle);
            $savedPartialMap = $import->map ? array_reduce(
                $fieldHandleArray,
                static fn ($value, $part) => $value && is_iterable($value)
                    ? $value[$part] ?? null
                    : null,
                $import->map
            ) : null;

            if ($currentPartialMap != $savedPartialMap) {
                $map = $import->map;
                $ref = &$map;
                foreach ($fieldHandleArray as $part) {
                    if (! is_array($ref[$part] ?? null)) {
                        $ref[$part] = [];
                    }
                    $ref = &$ref[$part];
                }
                $ref = $currentPartialMap;
                unset($ref);
                $import->map($map);
            }
        }

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

        $currentUser = $this->request->craftUser();

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
            ->submitButtonLabel(t('Apply'))
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

    public function storeNestedFieldMapping(): Response
    {
        [$fieldUid, $field, $importUid, $import] = $this->fieldImportUids();

        $fieldHandle = ImportHelper::bracketsToDots($this->request->input('fieldHandle', $field->handle));

        // validate the map fragment;
        // if it errors, a toast notification will show with the error
        $this->request->validate([
            'fieldHandle' => ['required', 'string', 'max:255'],
            "map.$fieldHandle" => [
                'required',
                'array',
                fn ($attribute, $value, Closure $fail, Validator $validator) => $import::validateMap($value, $attribute, $fail, $validator, ['field' => $field]),
            ],
        ]);

        $map = $this->request->input("map.$fieldHandle");

        $map = array_map(ImportHelper::ensureCleanArray(...), $map);

        // and return it
        return $this->asJsonSuccess(null, ['fieldHandle' => $fieldHandle, 'map' => $map]);
    }

    private function fieldImportUids(): array
    {
        $fieldUid = $this->request->input('fieldUid');
        abort_if(empty($fieldUid), 400, 'No field UID provided');

        $field = Fields::getFieldByUid($fieldUid);
        abort_if(is_null($field), 400, 'Invalid field UID.');

        $importUid = $this->request->input('importUid');
        abort_if(empty($importUid), 400, 'No import UID provided');

        $import = $this->importService->getConfigByUid($importUid);
        abort_if(is_null($import), 400, 'Invalid import UID.');

        return [$fieldUid, $field, $importUid, $import];
    }

    public function destroy(): Response
    {
        $uid = $this->request->input('uid');

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
        $currentUser = $this->request->craftUser();

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
            ->title(! isset($import->uid) ? t('Create a new import config') : t('Edit {name} import config', ['name' => $import->name]))
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
    public function run(): Response
    {
        $handle = $this->request->input('handle');

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
