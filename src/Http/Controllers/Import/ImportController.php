<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Import;

use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\ImportableElementContainerFieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\ImportEditViewModel;
use CraftCms\Cms\Http\ViewModels\ImportMapViewModel;
use CraftCms\Cms\Http\ViewModels\ImportStepFormViewModel;
use CraftCms\Cms\Import\Data\Import as ImportData;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\Imports;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\ImportHelper;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

class ImportController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        private Request $request,
        GeneralConfig $generalConfig,
        private readonly Import $importService,
        private readonly Imports $importsService,
        private readonly Fields $fieldsService,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(): InertiaResponse
    {
        $currentUser = $this->request->craftUser();

        return Inertia::render('import/Index', [
            'title' => t('Imports'),
            'crumbs' => [
                ['label' => t('Import')],
            ],
            'readOnly' => $this->readOnly,
            'canSave' => ! $this->readOnly && (bool) $currentUser?->can('saveImports'),
            'canDelete' => ! $this->readOnly && (bool) $currentUser?->can('deleteImports'),
            'canTrigger' => (bool) $currentUser?->can('triggerImports'),
            'editableImports' => $this->importsService->getEditableImports()
                ->map(fn (ImportData $import) => $this->importRow($import, true))
                ->values()
                ->all(),
            'nonEditableImports' => $this->importsService->getNonEditableImports()
                ->map(fn (ImportData $import) => $this->importRow($import, false))
                ->values()
                ->all(),
        ]);
    }

    /** @return array<string, mixed> */
    private function importRow(ImportData $import, bool $editable): array
    {
        return [
            'uid' => $import->uid,
            'name' => $import->name,
            'handle' => $import->handle,
            'description' => $import->description,
            'stepCount' => count($import->steps ?? []),
            'stepLabels' => array_map(
                $this->stepTypeLabel(...),
                $import->steps ?? [],
            ),
            'editUrl' => $editable ? Url::cpUrl('import/'.$import->handle) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $step
     */
    private function stepTypeLabel(array $step): string
    {
        $type = $step['type'] ?? null;

        return is_string($type) && class_exists($type) ? $type::displayName() : t('Unknown importer');
    }

    public function create(): CpScreenResponse
    {
        $old = $this->request->session()->get('import');

        return $this->cpScreenResponse(! empty($old) ? new ImportData($old) : new ImportData);
    }

    public function edit(?ImportData $import = null, ?string $handle = null): CpScreenResponse
    {
        $handle ??= $import->handle ?? $this->request->input('handle');

        if (is_null($handle)) {
            return $this->create();
        }

        abort_if(is_null($found = $this->importsService->getImportByHandle($handle)), 404, 'Import not found');
        abort_if(! $found->isEditable(), 400, "This import is not editable: $found->handle");

        $old = $this->request->session()->get('import');
        if (! empty($old)) {
            $import = new ImportData($old);
        }

        $import ??= $found;

        return $this->cpScreenResponse($import);
    }

    public function store(): Response
    {
        $importUid = $this->request->input('uid');

        $this->request->validate([
            'uid' => ['nullable', 'string', 'max:36'],
        ]);

        if ($importUid) {
            abort_if(is_null($import = $this->importsService->getImportByUid($importUid, true)), 400, "Invalid import UID: $importUid");
        } else {
            $import = new ImportData;
        }

        $import->name($this->request->input('name', $import->name));
        $import->handle($this->request->input('handle', $import->handle));
        $import->description($this->request->input('description', $import->description));
        $import->steps($this->normalizeSteps($this->request->input('steps', $import->steps ?? [])));

        if (! $this->importsService->saveImport($import)) {
            return $this->asModelFailure($import, t('Couldn’t save import.'), 'import');
        }

        return $this->asModelSuccess(
            $import,
            t('Import saved.'),
            'import',
        );
    }

    /**
     * Normalizes posted steps into the stored shape, decoding any mapping trees that arrived
     * as JSON strings (as file-based configs and plugins may still send them).
     *
     * @param  array<array-key, array<string, mixed>>  $steps  The posted steps.
     * @return array<int, array<string, mixed>>
     */
    private function normalizeSteps(array $steps): array
    {
        return array_values(array_map(function (array $step): array {
            $settings = $step['settings'] ?? [];

            foreach (['map', 'matchCriteria', 'clearableItems', 'keepMissingNestedElements'] as $key) {
                if (isset($settings[$key])) {
                    $settings[$key] = ImportHelper::decodeRecursive($settings[$key]);
                }
            }

            $batchSize = $step['batchSize'] ?? null;

            return [
                'uid' => $step['uid'] ?? null,
                'type' => $step['type'] ?? null,
                'file' => $step['file'] ?? null,
                'transformer' => $step['transformer'] ?? null,
                'batchSize' => $batchSize === '' || $batchSize === null ? null : (int) $batchSize,
                'settings' => $settings,
            ];
        }, $steps));
    }

    /**
     * Returns the form payload for a single step, built from the posted draft step rather than
     * anything persisted, so a step can be configured before the import is ever saved.
     */
    public function stepSettings(): JsonResponse
    {
        $importer = $this->draftImporter(requireType: false);
        $batchSize = $this->request->input('step.batchSize');

        return new JsonResponse([
            'form' => new ImportStepFormViewModel(
                $importer,
                $this->importService,
                app(FormResolver::class),
                $this->readOnly,
                (bool) $this->request->craftUser()?->can('saveImports'),
                $batchSize === null || $batchSize === '' ? null : (int) $batchSize,
            )->form(),
            'canMap' => $this->canMapStep($importer),
        ]);
    }

    /**
     * Returns whether a step has settled enough for its data to be mapped.
     *
     * An element importer resolves its field layout from whatever it's importing into — an
     * entry type, a volume — so until that's chosen there are no destination columns to map
     * onto, and without a valid file there are no incoming ones either.
     *
     * @param  BaseImporter|null  $importer  The step's importer, or null when it has no type yet.
     */
    private function canMapStep(?BaseImporter $importer): bool
    {
        if ($importer === null || ! BaseImporter::isFileValid($importer->file)) {
            return false;
        }

        return ! $importer instanceof ElementImporter || ! empty($importer->fieldLayout);
    }

    /**
     * Validates a single draft step, for the slideout to check before it lets the step close.
     */
    public function validateStep(): JsonResponse
    {
        $data = $this->request->validate([
            'step' => ['required', 'array'],
            'step.uid' => ['nullable', 'string', 'max:36'],
            'step.type' => ['nullable', 'string', Rule::in($this->importService->getAllImporterTypes())],
            'step.file' => ['nullable', 'string'],
            'step.transformer' => ['nullable', 'string'],
            'step.batchSize' => ['nullable', 'integer'],
            'step.settings' => ['nullable', 'array'],
        ]);

        $step = $data['step'];
        $step['settings'] = ImportHelper::decodeRecursive($step['settings'] ?? []);

        $errors = ImportData::stepErrors($step);

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * Returns the mapping structure for a draft step: its destination columns, the source
     * columns read from its file, and a best guess at a mapping between the two.
     */
    public function stepMapping(): JsonResponse
    {
        $importer = $this->draftImporter();

        // the panel's button is hidden until this passes, so reaching here means the step
        // changed between the last refresh and the click
        if (! $this->canMapStep($importer)) {
            return $this->asJsonSuccess(null, [
                'available' => false,
                'message' => t('Choose what this step imports into before mapping its data.'),
            ]);
        }

        $destinationCols = $importer->getDestinationCols();
        $sourceDataCols = $importer->getSourceDataCols();

        return $this->asJsonSuccess(null, [
            'available' => true,
            'destinationCols' => $destinationCols,
            'sourceDataCols' => $sourceDataCols,
            'values' => new ImportMapViewModel($importer)->values(),
            'suggestions' => ImportHelper::suggestMapValues($destinationCols, $sourceDataCols, $importer->map),
        ]);
    }

    /**
     * Returns the destination columns for a container field or property, for the nested
     * mapping panel.
     *
     * Only the structure: the panel is opened around client state the mapping panel already
     * holds, so there is nothing to relay back and forth.
     */
    public function nestedMappingCols(): JsonResponse
    {
        $importer = $this->draftImporter();

        $fieldUid = $this->request->input('fieldUid');
        $field = ! empty($fieldUid) ? $this->fieldsService->getFieldByUid($fieldUid) : null;

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

        // if it's a property, and the target element type has the method that takes care of
        // importing into that property, use that method
        $targetClass = $importer::targetClass();
        if (! $field && $fieldIsProperty && method_exists($targetClass, 'getDestinationColsForProperty')) {
            $groups[] = [
                'providerName' => null,
                'destinationCols' => $targetClass::getDestinationColsForProperty($importer, $fieldHandle),
            ];
        }

        $sourceDataCols = $importer->getSourceDataCols();
        $allDestinationCols = array_merge(...array_column($groups, 'destinationCols'));

        // the top-level columns are thrown in so a heading that exactly matches one of them
        // isn't also guessed at in here, then the guesses are narrowed back down to this panel
        $suggestions = ImportHelper::suggestMapValues(
            array_merge($importer->getDestinationCols(), $allDestinationCols),
            $sourceDataCols,
            $importer->map ?? [],
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
     * Builds a transient importer from the posted draft step. Nothing is looked up in the
     * database, so this works for a step that has never been saved.
     *
     * @param  bool  $requireType  Whether the step must name an importer type.
     */
    private function draftImporter(bool $requireType = true): ?BaseImporter
    {
        $data = $this->request->validate([
            'step' => ['required', 'array'],
            'step.uid' => ['nullable', 'string', 'max:36'],
            'step.type' => [$requireType ? 'required' : 'nullable', 'nullable', 'string', Rule::in($this->importService->getAllImporterTypes())],
            'step.file' => ['nullable', 'string'],
            'step.transformer' => ['nullable', 'string'],
            'step.batchSize' => ['nullable', 'integer'],
            'step.settings' => ['nullable', 'array'],
        ]);

        $step = $data['step'];

        if (empty($step['type'])) {
            return null;
        }

        $step['settings'] = ImportHelper::decodeRecursive($step['settings'] ?? []);

        return $this->importsService::createImporter($step);
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

    public function duplicate(): Response
    {
        $uid = $this->request->input('uid');

        if (! $uid) {
            throw ValidationException::withMessages([
                'id' => t('uid is required.'),
            ]);
        }

        $import = $this->importsService->getImportByUid($uid);

        abort_if(is_null($import), 404, "Invalid import UID: $uid");
        abort_if(! $import->isEditable(), 400, "This import is not editable, so it can’t be duplicated via the Control Panel: $uid");

        $this->importsService->duplicateImport($import);

        return $this->asSuccess(t('“{name}” duplicated.', [
            'name' => $import->name,
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

        $import = $this->importsService->getImportByUid($uid);

        abort_if(is_null($import), 404, "Invalid import UID: $uid");
        abort_if(! $import->isEditable(), 400, "This import is not editable, so it can’t be deleted via the Control Panel: $uid");

        $this->importsService->deleteImport($import);

        return $this->asSuccess(t('“{name}” deleted.', [
            'name' => $import->name,
        ]));
    }

    public function run(): Response
    {
        $uid = $this->request->input('uid');
        $handle = $this->request->input('handle');

        abort_if(is_null($uid) && is_null($handle), 400, 'An import uid or handle is required.');

        $import = $uid !== null
            ? $this->importsService->getImportByUid($uid)
            : $this->importsService->getImportByHandle($handle);

        abort_if(is_null($import), 400, 'Import not found.');

        try {
            $this->importService->dispatchImport($import);
        } catch (Throwable $e) {
            ImportLog::warning("Import failed: {$e->getMessage()}");

            return $this->asFailure(t('Import could not be started.'));
        }

        return $this->asSuccess(t('Import started'));
    }

    private function cpScreenResponse(ImportData $import): CpScreenResponse
    {
        $currentUser = $this->request->craftUser();
        $canSave = (bool) $currentUser?->can('saveImports');
        $editable = ! $this->readOnly && $canSave;

        return new CpScreenResponse()
            ->title(! isset($import->uid) ? t('Create a new import') : t('Edit {name} import', ['name' => $import->name]))
            ->addCrumb(t('Import'), 'import')
            ->formAttributes(['action' => action([self::class, 'store'])])
            ->inertiaPage('import/Edit', new ImportEditViewModel(
                $import,
                $this->importService,
                app(FormResolver::class),
                $this->readOnly,
                $canSave,
            ))
            ->when(
                $editable,
                callback: function (CpScreenResponse $response) use ($import) {
                    $response
                        ->action('import/save')
                        ->redirectUrl('import/{handle}');

                    if ($import->isEditable()) {
                        $response->addAltAction(t('Delete'), [
                            'variant' => 'danger',
                            'action' => [
                                'type' => 'http',
                                'method' => 'DELETE',
                                'url' => action([self::class, 'destroy']),
                                'body' => [
                                    'uid' => $import->uid,
                                    'redirect' => Crypt::encrypt(action([self::class, 'index'])),
                                ],
                                'confirm' => t('Are you sure you want to delete “{name}”?', [
                                    'name' => $import->name,
                                ]),
                            ],
                        ]);

                        $response->addAltAction(t('Run this import'), [
                            'action' => [
                                'type' => 'http',
                                'method' => 'POST',
                                'url' => action([self::class, 'run']),
                                'body' => [
                                    'uid' => $import->uid,
                                    'redirect' => Crypt::encrypt(action([self::class, 'index'])),
                                ],
                                'confirm' => t('Are you sure you want to run “{name}”?', [
                                    'name' => $import->name,
                                ]),
                            ],
                        ]);
                    }
                },
            );
    }
}
