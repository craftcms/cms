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
use CraftCms\Cms\Http\ViewModels\ImportPlanEditViewModel;
use CraftCms\Cms\Http\ViewModels\ImportPlanMapViewModel;
use CraftCms\Cms\Http\ViewModels\ImportPlanStepFormViewModel;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\ImportPlan;
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

class ImportPlansController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        private Request $request,
        GeneralConfig $generalConfig,
        private readonly Import $importService,
        private readonly ImportPlan $importsService,
        private readonly Fields $fieldsService,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(): InertiaResponse
    {
        $currentUser = $this->request->craftUser();

        return Inertia::render('import/Index', [
            'title' => t('Import Plans'),
            'crumbs' => [
                ['label' => t('Import')],
            ],
            'readOnly' => $this->readOnly,
            'canSave' => ! $this->readOnly && (bool) $currentUser?->can('saveImportPlans'),
            'canDelete' => ! $this->readOnly && (bool) $currentUser?->can('deleteImportPlans'),
            'canTrigger' => (bool) $currentUser?->can('triggerImportPlans'),
            'editableImportPlans' => $this->importsService->getEditableImportPlans()
                ->map(fn (ImportPlanData $importPlan) => $this->importRow($importPlan, true))
                ->values()
                ->all(),
            'nonEditableImportPlans' => $this->importsService->getNonEditableImportPlans()
                ->map(fn (ImportPlanData $importPlan) => $this->importRow($importPlan, false))
                ->values()
                ->all(),
        ]);
    }

    /** @return array<string, mixed> */
    private function importRow(ImportPlanData $importPlan, bool $editable): array
    {
        return [
            'uid' => $importPlan->uid,
            'name' => $importPlan->name,
            'handle' => $importPlan->handle,
            'description' => $importPlan->description,
            'stepCount' => count($importPlan->steps ?? []),
            'stepLabels' => array_map(
                $this->stepTypeLabel(...),
                $importPlan->steps ?? [],
            ),
            'editUrl' => $editable ? Url::cpUrl('import/'.$importPlan->handle) : null,
        ];
    }

    private function stepTypeLabel(BaseImporter $step): string
    {
        $type = $step::class ?? null;

        return is_string($type) && class_exists($type) ? $type::displayName() : t('Unknown importer');
    }

    public function create(): CpScreenResponse
    {
        $old = $this->request->session()->get('import');

        return $this->cpScreenResponse(! empty($old) ? new ImportPlanData($old) : new ImportPlanData);
    }

    public function edit(?ImportPlanData $importPlan = null, ?string $handle = null): CpScreenResponse
    {
        $handle ??= $importPlan->handle ?? $this->request->input('handle');

        if (is_null($handle)) {
            return $this->create();
        }

        abort_if(is_null($found = $this->importsService->getImportPlanByHandle($handle)), 404, 'Import plan not found');
        abort_if(! $found->isEditable(), 400, "This import plan is not editable: $found->handle");

        $old = $this->request->session()->get('import');
        if (! empty($old)) {
            $importPlan = new ImportPlanData($old);
        }

        $importPlan ??= $found;

        return $this->cpScreenResponse($importPlan);
    }

    public function store(): Response
    {
        $importUid = $this->request->input('uid');

        $this->request->validate([
            'uid' => ['nullable', 'string', 'max:36'],
        ]);

        if ($importUid) {
            abort_if(is_null($importPlan = $this->importsService->getImportPlanByUid($importUid, true)), 400, "Invalid import plan UID: $importUid");
        } else {
            $importPlan = new ImportPlanData(['editable' => true]);
        }

        $importPlan->name($this->request->input('name', $importPlan->name));
        $importPlan->handle($this->request->input('handle', $importPlan->handle));
        $importPlan->description($this->request->input('description', $importPlan->description));
        $importPlan->steps($this->request->input('steps', $importPlan->steps ?? []));

        if (! $this->importsService->saveImportPlan($importPlan)) {
            return $this->asModelFailure($importPlan, t('Couldn’t save import plan.'), 'import');
        }

        return $this->asModelSuccess(
            $importPlan,
            t('Import plan saved.'),
            'import',
        );
    }

    /**
     * Returns the form payload for a single step, built from the posted draft step rather than
     * anything persisted, so a step can be configured before the import plan is ever saved.
     */
    public function stepSettings(): JsonResponse
    {
        $importer = $this->draftImporter(requireType: false);
        $batchSize = $this->request->input('step.batchSize');

        return new JsonResponse([
            'form' => new ImportPlanStepFormViewModel(
                $importer,
                $this->importService,
                app(FormResolver::class),
                $this->readOnly,
                (bool) $this->request->craftUser()?->can('saveImportPlans'),
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

        $errors = ImportPlanData::stepErrors($step);

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
            'values' => new ImportPlanMapViewModel($importer)->values(),
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

        return ImportPlan::createImporter($step);
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

        $importPlan = $this->importsService->getImportPlanByUid($uid);

        abort_if(is_null($importPlan), 404, "Invalid import plan UID: $uid");
        abort_if(! $importPlan->isEditable(), 400, "This import plan is not editable, so it can’t be duplicated via the Control Panel: $uid");

        $this->importsService->duplicateImportPlan($importPlan);

        return $this->asSuccess(t('“{name}” duplicated.', [
            'name' => $importPlan->name,
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

        $importPlan = $this->importsService->getImportPlanByUid($uid);

        abort_if(is_null($importPlan), 404, "Invalid import plan UID: $uid");
        abort_if(! $importPlan->isEditable(), 400, "This import plan is not editable, so it can’t be deleted via the Control Panel: $uid");

        $this->importsService->deleteImportPlan($importPlan);

        return $this->asSuccess(t('“{name}” deleted.', [
            'name' => $importPlan->name,
        ]));
    }

    public function run(): Response
    {
        $uid = $this->request->input('uid');
        $handle = $this->request->input('handle');

        abort_if(is_null($uid) && is_null($handle), 400, 'An import plan uid or handle is required.');

        $importPlan = $uid !== null
            ? $this->importsService->getImportPlanByUid($uid)
            : $this->importsService->getImportPlanByHandle($handle);

        abort_if(is_null($importPlan), 400, 'Import plan not found.');

        try {
            $this->importService->dispatchImport($importPlan);
        } catch (Throwable $e) {
            ImportLog::warning("Import failed: {$e->getMessage()}");

            return $this->asFailure(t('Import could not be started.'));
        }

        return $this->asSuccess(t('Import started'));
    }

    private function cpScreenResponse(ImportPlanData $importPlan): CpScreenResponse
    {
        $currentUser = $this->request->craftUser();
        $canSave = (bool) $currentUser?->can('saveImportPlans');
        $editable = ! $this->readOnly && $canSave;

        return new CpScreenResponse()
            ->title(! isset($importPlan->uid) ? t('Create a new import plan') : t('Edit {name} import plan', ['name' => $importPlan->name]))
            ->addCrumb(t('Import'), 'import')
            ->formAttributes(['action' => action([self::class, 'store'])])
            ->inertiaPage('import/Edit', new ImportPlanEditViewModel(
                $importPlan,
                $this->importService,
                app(FormResolver::class),
                $this->readOnly,
                $canSave,
            ))
            ->when(
                $editable,
                callback: function (CpScreenResponse $response) use ($importPlan) {
                    $response
                        ->action('import/save')
                        ->redirectUrl('import/{handle}');

                    if ($importPlan->isEditable()) {
                        $response->addAltAction(t('Delete'), [
                            'variant' => 'danger',
                            'action' => [
                                'type' => 'http',
                                'method' => 'DELETE',
                                'url' => action([self::class, 'destroy']),
                                'body' => [
                                    'uid' => $importPlan->uid,
                                    'redirect' => Crypt::encrypt(action([self::class, 'index'])),
                                ],
                                'confirm' => t('Are you sure you want to delete “{name}”?', [
                                    'name' => $importPlan->name,
                                ]),
                            ],
                        ]);

                        $response->addAltAction(t('Run this import plan'), [
                            'action' => [
                                'type' => 'http',
                                'method' => 'POST',
                                'url' => action([self::class, 'run']),
                                'body' => [
                                    'uid' => $importPlan->uid,
                                    'redirect' => Crypt::encrypt(action([self::class, 'index'])),
                                ],
                                'confirm' => t('Are you sure you want to run “{name}”?', [
                                    'name' => $importPlan->name,
                                ]),
                            ],
                        ]);
                    }
                },
            );
    }
}
