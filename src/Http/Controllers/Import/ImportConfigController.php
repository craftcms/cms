<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Import;

use Closure;
use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\ImportableElementContainerFieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\ImportConfigEditViewModel;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\ImportConfig;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\ImportHelper;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

class ImportConfigController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        private Request $request,
        private GeneralConfig $generalConfig,
        private readonly Import $importService,
        private readonly ImportConfig $importConfigService,
        private readonly Fields $fieldsService,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(): InertiaResponse
    {
        $currentUser = $this->request->craftUser();

        return Inertia::render('import/configs/Index', [
            'title' => t('Import configs'),
            'crumbs' => [
                ['label' => t('Import'), 'href' => Url::cpUrl('import')],
            ],
            'readOnly' => $this->readOnly,
            'canSave' => ! $this->readOnly && (bool) $currentUser?->can('saveImportConfigs'),
            'canDelete' => ! $this->readOnly && (bool) $currentUser?->can('deleteImportConfigs'),
            'canTriggerRuns' => (bool) $currentUser?->can('triggerImportRuns'),
            'editableConfigs' => $this->importConfigService->getEditableConfigs()
                ->map(fn (BaseImporter $config) => $this->editableConfigRow($config))
                ->values()
                ->all(),
            'nonEditableConfigs' => $this->importConfigService->getNonEditableConfigs()
                ->map(fn (BaseImporter $config) => $this->nonEditableConfigRow($config))
                ->values()
                ->all(),
        ]);
    }

    /** @return array<string, mixed> */
    private function editableConfigRow(BaseImporter $config): array
    {
        return [
            'uid' => $config->uid,
            'name' => $config->name,
            'handle' => $config->handle,
            'file' => $config->file,
            'site' => property_exists($config, 'site') ? $config->site?->name : null,
            'isElementImporter' => $config::isElementImporter(),
            'importType' => $config::displayName(),
            'editUrl' => Url::cpUrl('import/configs/'.$config->handle),
        ];
    }

    /** @return array<string, mixed> */
    private function nonEditableConfigRow(BaseImporter $config): array
    {
        return [
            'handle' => $config->handle,
            'name' => $config->name,
            'site' => property_exists($config, 'site') ? $config->site?->name : null,
            'isElementImporter' => $config::isElementImporter(),
            'importType' => $config::displayName(),
            'transformer' => $config->transformerAsString(),
            'hasMap' => $config->map !== [],
        ];
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

    public function refreshForm(): JsonResponse
    {
        $data = $this->request->validate([
            'values' => ['required', 'array'],
            'values.uid' => ['nullable', 'string'],
            'values.type' => ['nullable', 'string', Rule::in($this->importService->getAllImporterTypes())],
            'values.name' => ['nullable', 'string'],
            'values.handle' => ['nullable', 'string'],
            'values.description' => ['nullable', 'string'],
            'values.file' => ['nullable', 'string'],
            'values.transformer' => ['nullable', 'string'],
            'values.settings' => ['nullable', 'array'],
            'scope' => ['present', 'array', 'size:0'],
        ]);
        $values = $data['values'];
        $type = $values['type'] ?? null;

        $importer = null;
        if ($type) {
            $importer = new $type(['uid' => $values['uid'] ?? null]);
            $importer->name($values['name'] ?? '');
            $importer->handle($values['handle'] ?? '');
            $importer->description($values['description'] ?? null);
            $importer->file($values['file']);
            $importer->transformer($values['transformer']);

            $importer->refreshSettingsForm($values['settings'] ?? []);
        }

        return new JsonResponse([
            'form' => new ImportConfigEditViewModel($importer, $this->importService, app(FormResolver::class))->form(),
        ]);
    }

    public function edit(?BaseImporter $importer = null, ?string $handle = null): CpScreenResponse
    {
        $handle ??= $importer->handle ?? $this->request->input('handle');

        if (is_null($handle)) {
            return $this->create();
        }

        abort_if(is_null($found = $this->importConfigService->getConfigByHandle($handle)), 404, 'Import config not found');
        abort_if(! $found->isEditable(), 400, "This import config is not editable: $found->handle");

        $importer ??= $found;

        return $this->cpScreenResponse($importer);
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
            abort_if(is_null($import = $this->importConfigService->getConfigByUid($importConfigUid)), 400, "Invalid import config UID: $importConfigUid");
        } else {
            $import = new ($this->request->input('type'));
        }

        $this->request->validate($import::getRules());

        $import->name($this->request->input('name', $import->name));
        $import->handle($this->request->input('handle', $import->handle));
        $import->description($this->request->input('description', $import->description));
        $import->file($this->request->input('file', $import->file));
        $import->transformer($this->request->input('transformer', $import->transformer));

        $import->storeSettings($this->request->input('settings', []));

        $import->map($this->request->input('settings.map', $import->map));
        $import->matchCriteria($this->request->input('settings.matchCriteria', $import->matchCriteria));

        if (! $this->importConfigService->saveConfig($import)) {
            // Flash::fail(t('Couldn’t save import config.'));
            return $this->asModelFailure($import, t('Couldn’t save import config.'), 'import');
        }

        return $this->asModelSuccess(
            $import,
            t('Import config saved.'),
            'import',
        );
    }

    public function storeMap(): Response
    {
        $importConfigUid = $this->request->input('importUid');
        abort_if(empty($importConfigUid), 400, 'No import config UID provided');

        $this->request->validate([
            'importUid' => ['string', 'max:36'],
        ]);

        abort_if(is_null($import = $this->importConfigService->getConfigByUid($importConfigUid)), 400, "Invalid import config UID: $importConfigUid");

        $this->request->validate([
            'fieldLayoutId' => ['nullable', 'integer'],
            'map' => [
                'required',
                'array',
                fn ($attribute, $value, Closure $fail, Validator $validator) => $import::validateMap($value, $attribute, $fail, $validator),
            ],
            'matchCriteria' => [
                'nullable',
                'array',
                // we're intentionally using validateMap() here as we basically want to check the same thing for map and matchCriteria
                fn ($attribute, $value, Closure $fail, Validator $validator) => $import::validateMap($value, $attribute, $fail, $validator),
            ],
            'clearableItems' => [
                'nullable',
                'array',
                // we're intentionally using validateMap() here as we basically want to check the same thing for map and clearableItems
                fn ($attribute, $value, Closure $fail, Validator $validator) => $import::validateMap($value, $attribute, $fail, $validator),
            ],
        ]);

        if (property_exists($import, 'fieldLayoutId')) {
            /** @phpstan-ignore-next-line */
            $import->fieldLayoutId($this->request->input('fieldLayoutId', $import->fieldLayoutId));
        }

        // container branches used to arrive as JSON strings from the Twig form's hidden
        // inputs; the Vue page posts real nested objects, but plugins and file-based
        // configs can still send the encoded shape
        $import->map(ImportHelper::decodeRecursive($this->request->input('map', $import->map)));
        $import->matchCriteria(ImportHelper::decodeRecursive($this->request->input('matchCriteria', $import->matchCriteria)));
        $import->clearableItems(ImportHelper::decodeRecursive($this->request->input('clearableItems', $import->clearableItems ?? [])));
        if ($import instanceof ElementImporter) {
            $import->keepMissingNestedElements(ImportHelper::decodeRecursive($this->request->input('keepMissingNestedElements', $import->keepMissingNestedElements ?? [])));
        }

        if (! $this->importConfigService->saveConfig($import)) {
            // Flash::fail(t('Couldn’t save import config.'));
            return $this->asModelFailure($import, t('Couldn’t save import config.'), 'import');
        }

        return $this->asModelSuccess(
            $import,
            t('Import config saved.'),
            'import',
        );
    }

    /**
     * Returns the destination columns for a container field or property, for the nested
     * mapping panel.
     *
     * Only the structure: the panel is opened around client state the map page already
     * holds, so there is nothing to relay back and forth.
     */
    public function nestedMappingCols(): JsonResponse
    {
        [, $field, , $import] = $this->fieldImportUids();

        $fieldHandle = $this->request->input('fieldHandle');
        $fieldIsProperty = $this->request->boolean('fieldIsProperty');

        $fieldName = $field instanceof FieldInterface ? $field->name : $fieldHandle;
        $groups = [];

        if ($field instanceof ImportableElementContainerFieldInterface) {
            foreach ($field->getFieldLayoutProviders() as $provider) {
                $groups[] = [
                    'providerName' => $provider instanceof Chippable ? $provider->getUiLabel() : $provider->getHandle(),
                    'destinationCols' => ImportHelper::getDestinationColsForFieldLayout(
                        $provider->getFieldLayout(),
                        $field,
                        $provider,
                        $fieldHandle,
                    ),
                ];
            }
        }

        // if it's a property, and we have the method that takes care of importing into that property, use that method
        if (! $field && $fieldIsProperty && method_exists($import->className, 'getDestinationColsForProperty')) {
            $groups[] = [
                'providerName' => null,
                'destinationCols' => $import->className::getDestinationColsForProperty($import, $fieldHandle),
            ];
        }

        $sourceDataCols = $import->getSourceDataCols();
        $allDestinationCols = array_merge(...array_column($groups, 'destinationCols'));

        // the top-level columns are thrown in so a heading that exactly matches one of them
        // isn't also guessed at in here, then the guesses are narrowed back down to this panel
        $suggestions = ImportHelper::suggestMapValues(
            array_merge($import->getDestinationCols(), $allDestinationCols),
            $sourceDataCols,
            $import->map ?? [],
        );
        $rootPath = implode('.', Arr::bracketsToArray((string) $fieldHandle));

        return $this->asJsonSuccess(null, [
            'title' => t('Edit map for {fieldName}', ['fieldName' => $fieldName]),
            'fieldName' => $fieldName,
            'groups' => $groups,
            'sourceDataCols' => $sourceDataCols,
            'suggestions' => self::subtree($suggestions, $rootPath),
        ]);
    }

    /**
     * Returns a tree holding only `$path`'s branch of `$tree`, still rooted at the top level.
     */
    private static function subtree(array $tree, string $path): array
    {
        $branch = Arr::get($tree, $path);

        if ($branch === null) {
            return [];
        }

        $subtree = [];
        Arr::set($subtree, $path, $branch);

        return $subtree;
    }

    private function fieldImportUids(): array
    {
        $fieldUid = $this->request->input('fieldUid');

        $field = null;
        if (! empty($fieldUid)) {
            $field = $this->fieldsService->getFieldByUid($fieldUid);
        }

        $importUid = $this->request->input('importUid');
        abort_if(empty($importUid), 400, 'No import UID provided');

        $import = $this->importConfigService->getConfigByUid($importUid);
        abort_if(is_null($import), 400, 'Invalid import UID.');

        return [$fieldUid, $field, $importUid, $import];
    }

    public function duplicate(): Response
    {
        $uid = $this->request->input('uid');

        if (! $uid) {
            throw ValidationException::withMessages([
                'id' => t('uid is required.'),
            ]);
        }

        $config = $this->importConfigService->getConfigByUid($uid);

        abort_if(is_null($config), 404, "Invalid import config UID: $uid");
        abort_if(! $config->isEditable(), 400, "This import config is not editable, so it can’t be duplicated via the Control Panel: $uid");

        $this->importConfigService->duplicateConfig($config);

        return $this->asSuccess(t('“{name}” duplicated.', [
            'name' => $config->name,
        ]));
    }

    public function destroy(): Response
    {
        $uid = $this->request->input('uid');

        if (! $uid) {
            throw ValidationException::withMessages([
                'id' => t('uid is required.'),
            ]);
        }

        $config = $this->importConfigService->getConfigByUid($uid);

        abort_if(is_null($config), 404, "Invalid import config UID: $uid");
        abort_if(! $config->isEditable(), 400, "This import config is not editable, so it can’t be deleted via the Control Panel: $uid");

        $this->importConfigService->deleteConfig($config);

        return $this->asSuccess(t('“{name}” deleted.', [
            'name' => $config->name,
        ]));
    }

    private function cpScreenResponse(?BaseImporter $importer = null): CpScreenResponse
    {
        $currentUser = $this->request->craftUser();
        $canSave = (bool) $currentUser?->can('saveImportConfigs');
        $editable = ! $this->readOnly && $canSave;

        return new CpScreenResponse()
            ->title(! isset($importer->uid) ? t('Create a new import config') : t('Edit {name} import config', ['name' => $importer->name]))
            ->addCrumb(t('Import'), 'import')
            ->addCrumb(t('Configs'), 'import/configs')
            ->formAttributes(['action' => action([self::class, 'store'])])
            ->inertiaPage('import/configs/Edit', new ImportConfigEditViewModel(
                $importer,
                $this->importService,
                app(FormResolver::class),
                $this->readOnly,
                $canSave,
            ))
            ->when(
                $editable,
                callback: function (CpScreenResponse $response) use ($importer) {
                    $response
                        ->action('import/configs/save')
                        ->redirectUrl('import/configs/{handle}');

                    if ($importer?->isEditable()) {
                        $response->addAltAction(t('Delete'), [
                            'variant' => 'danger',
                            'action' => [
                                'type' => 'http',
                                'method' => 'DELETE',
                                'url' => action([self::class, 'destroy']),
                                'body' => [
                                    'uid' => $importer->uid,
                                    'redirect' => Crypt::encrypt(action([self::class, 'index'])),
                                ],
                                'confirm' => t('Are you sure you want to delete “{name}”?', [
                                    'name' => $importer->name,
                                ]),
                            ],
                        ]);
                    }
                },
            );
    }

    // todo (iwona): this might be deleted - currently only used for file-based config, to run it from the configs screen
    public function run(): Response
    {
        $handle = $this->request->input('handle');

        abort_if(is_null($handle), 400, 'Import config handle is required.');
        abort_if(is_null($config = $this->importConfigService->getConfigByHandle($handle)), 400, 'Import config not found.');

        try {
            $this->importService->import($config);
        } catch (Throwable $e) {
            Log::warning("Import failed: {$e->getMessage()}");

            return $this->asFailure(t('Import could not be started.'));
        }

        return $this->asSuccess(t('Import started'));
    }
}
