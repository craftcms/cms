<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Import;

use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\ImportableContainerPropertiesInterface;
use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\ImportableElementContainerFieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\ImportPlanEditViewModel;
use CraftCms\Cms\Http\ViewModels\ImportPlanStepUiViewModel;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Data\ImportPlanIndexData;
use CraftCms\Cms\Import\Data\MappingColumnGroup;
use CraftCms\Cms\Import\Data\MappingValues;
use CraftCms\Cms\Import\Data\NestedMappingPayload;
use CraftCms\Cms\Import\Data\StepMappingPayload;
use CraftCms\Cms\Import\Data\StepUiPayload;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\ImportHelper;
use CraftCms\Cms\Import\ImportPlans;
use CraftCms\Cms\Import\ImportPreview;
use CraftCms\Cms\Import\SourceSuggestions;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiResolver;
use Exception;
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

/**
 * @since 6.0.0
 */
class ImportPlansController
{
    use RespondsWithFlash;

    public function __construct(
        private Request $request,
        private readonly Import $importService,
        private readonly ImportPlans $importsService,
        private readonly Fields $fieldsService,
    ) {}

    public function index(): InertiaResponse
    {
        $currentUser = $this->request->craftUser();

        return Inertia::render('import/Index', [
            'title' => t('Import Plans'),
            'crumbs' => [
                ['label' => t('Import')],
            ],
            'canSave' => (bool) $currentUser?->can('saveImportPlans'),
            'canDelete' => (bool) $currentUser?->can('deleteImportPlans'),
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

    private function importRow(ImportPlanData $importPlan, bool $editable): ImportPlanIndexData
    {
        $running = $this->importService->isRunning($importPlan);

        return new ImportPlanIndexData(
            uid: $importPlan->uid,
            name: (string) $importPlan->name,
            handle: (string) $importPlan->handle,
            description: $importPlan->description,
            stepCount: count($importPlan->steps ?? []),
            steps: array_values(array_map(
                fn (BaseImporter $step): array => [
                    'label' => $step::displayName(),
                    'source' => $step->source,
                    'enabled' => $step->enabled,
                    'url' => $editable && $step->uid
                        ? route('craft.cp.import.steps.edit', ['handle' => $importPlan->handle, 'uid' => $step->uid])
                        : null,
                ],
                $importPlan->steps ?? [],
            )),
            editable: $editable,
            running: $running,
            runningJobUrl: $running ? $this->runningJobUrl($importPlan) : null,
            lastRunFailed: $this->importService->lastRunFailed($importPlan),
        );
    }

    /**
     * Where a running plan's job is in the queue manager: the job itself once one's started,
     * or the queue manager while its jobs are still waiting.
     */
    private function runningJobUrl(ImportPlanData $importPlan): string
    {
        $jobUid = $this->importService->runningJobUid($importPlan);

        return route('craft.cp.utilities.show', array_filter([
            'id' => 'queue-manager',
            'extra' => $jobUid,
        ]));
    }

    /**
     * A new plan's wizard: its name, then each step's data and fields in turn, then a review.
     * Nothing is saved until the review, so the plan is posted whole.
     */
    public function create(): InertiaResponse
    {
        return Inertia::render('import/Create', [
            'title' => t('Create a new import plan'),
            'crumbs' => [
                new ActionItem()->label(t('Import'))->href(route('craft.cp.import.index')),
            ],
            'ui' => app(UiResolver::class)->resolve(
                Ui::make(ImportPlanEditViewModel::detailFields(isNew: true)),
                new UiContext(values: ['name' => null, 'handle' => null, 'description' => null]),
            ),
            'importerTypes' => array_map(fn (string $type): array => [
                'label' => $type::displayName(),
                'value' => $type,
            ], $this->importService->getAllImporterTypes()),
            'submit' => ['method' => 'post', 'url' => action([self::class, 'store'])],
            'runUrl' => $this->request->craftUser()?->can('triggerImportPlans') ? action([self::class, 'run']) : null,
            'editUrl' => route('craft.cp.import.edit', ['handle' => '__handle__']),
            'indexUrl' => route('craft.cp.import.index'),
        ]);
    }

    public function edit(?string $handle = null): CpScreenResponse|InertiaResponse
    {
        $handle ??= $this->request->input('handle');

        if (is_null($handle)) {
            return $this->create();
        }

        return $this->cpScreenResponse($this->editablePlan($handle));
    }

    /** The plan with this handle, or its unsaved changes if saving them just failed. */
    private function editablePlan(string $handle): ImportPlanData
    {
        abort_if(is_null($found = $this->importsService->getImportPlanByHandle($handle)), 404, 'Import plan not found');
        abort_if(! $found->isEditable(), 400, "This import plan is not editable: $found->handle");

        $old = $this->request->session()->get('import');

        return ! empty($old) ? new ImportPlanData($old) : $found;
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

    public function editStep(string $handle, string $uid): InertiaResponse
    {
        $importPlan = $this->editablePlan($handle);
        $step = collect($importPlan->serializeSteps() ?? [])->firstWhere('uid', $uid);

        abort_if($step === null, 404, 'Import step not found');

        return $this->stepScreen($importPlan, $step);
    }

    /**
     * What running the plan's enabled steps would do with the first rows of their data: the
     * values each row fills in, and which elements it creates or updates.
     *
     * Users who can save import plans get the posted steps, so the review follows the plan's
     * unsaved changes. Anyone else gets the saved plan, so they can't make the server fetch a
     * source of their choosing.
     */
    public function reviewSteps(ImportPreview $preview): JsonResponse
    {
        if ($this->request->craftUser()?->can('saveImportPlans')) {
            $steps = $this->request->validate([
                'steps' => ['nullable', 'array'],
                'steps.*' => ['array'],
            ])['steps'] ?? [];

            $importers = array_map(function (array $step): ?BaseImporter {
                $step['settings'] = ImportHelper::decodeRecursive($step['settings'] ?? []);

                return ImportPlans::createImporter($step);
            }, array_values($steps));
        } else {
            $uid = $this->request->validate([
                'uid' => ['required', 'string', 'max:36'],
            ])['uid'];

            abort_if(is_null($importPlan = $this->importsService->getImportPlanByUid($uid)), 404, 'Import plan not found');

            $importers = array_values($importPlan->steps ?? []);
        }

        return new JsonResponse([
            'steps' => $this->enabledSteps($importers, fn (BaseImporter $step) => $preview->review($step)),
        ]);
    }

    /**
     * Each enabled step, numbered by its place in the plan, with what the callback works out for it.
     * A step that's yet to be given a valid importer type has nothing to review, but keeps its place.
     *
     * @param  list<BaseImporter|null>  $importers
     * @return list<array{uid: string|null, number: int, label: string, source: string|null, preview: mixed}>
     */
    private function enabledSteps(array $importers, callable $callback): array
    {
        $steps = [];

        foreach ($importers as $index => $step) {
            if (! $step?->enabled) {
                continue;
            }

            $steps[] = [
                'uid' => $step->uid,
                'number' => $index + 1,
                'label' => $step::displayName(),
                'source' => $step->source,
                'preview' => $callback($step),
            ];
        }

        return $steps;
    }

    /**
     * Saves one step into its plan: replacing the step with its uid, adding it if there's none,
     * or adding a copy with a uid of its own when it's saved as a new step.
     */
    public function storeStep(): Response
    {
        $data = $this->request->validate([
            'planUid' => ['required', 'string', 'max:36'],
            'step' => ['required', 'array'],
            'step.uid' => ['required', 'string', 'max:36'],
            'asNew' => ['nullable', 'boolean'],
        ]);

        abort_if(is_null($importPlan = $this->importsService->getImportPlanByUid($data['planUid'], true)), 400, "Invalid import plan UID: {$data['planUid']}");

        // the validated data keeps only the step keys named in the rules
        $step = $this->request->input('step');
        if ($this->request->boolean('asNew')) {
            $step['uid'] = Str::uuid()->toString();
        }

        $steps = $importPlan->serializeSteps() ?? [];
        $index = collect($steps)->search(fn (array $existing) => $existing['uid'] === $step['uid']);

        if ($index === false) {
            $steps[] = $step;
        } else {
            $steps[$index] = $step;
        }

        $importPlan->steps($steps);

        if (! $this->importsService->saveImportPlan($importPlan)) {
            return $this->asModelFailure($importPlan, t('Couldn’t save import step.'), 'import');
        }

        // Save and continue editing posts no redirect, and lands on the step it saved
        return $this->asModelSuccess(
            $importPlan,
            t('Import step saved.'),
            'import',
            redirect: $this->getPostedRedirectUrl($importPlan)
                ?? route('craft.cp.import.steps.edit', ['handle' => $importPlan->handle, 'uid' => $step['uid']]),
        );
    }

    /** @param array<string, mixed> $step */
    private function stepScreen(ImportPlanData $importPlan, array $step): InertiaResponse
    {
        $canSave = (bool) $this->request->craftUser()?->can('saveImportPlans');
        $steps = $importPlan->serializeSteps() ?? [];
        $index = collect($steps)->search(fn (array $existing) => $existing['uid'] === $step['uid']);
        $planUrl = route('craft.cp.import.edit', ['handle' => $importPlan->handle]);

        return Inertia::render('import/StepEdit', [
            'title' => t('Edit step {number} of {name}', ['number' => $index + 1, 'name' => $importPlan->name]),
            'crumbs' => [
                new ActionItem()->label(t('Import'))->href(route('craft.cp.import.index')),
                new ActionItem()->label((string) $importPlan->name)->href($planUrl),
            ],
            'planUid' => $importPlan->uid,
            'step' => $step,
            // opened in a slideout, the step page links to itself to open in a new tab
            'editUrl' => route('craft.cp.import.steps.edit', ['handle' => $importPlan->handle, 'uid' => $step['uid']]),
            'editable' => $canSave,
            'submit' => ['method' => 'post', 'url' => action([self::class, 'storeStep'])],
            // Save goes back to the plan, and its other actions stay on the step
            'redirectUrl' => $canSave ? Crypt::encrypt($planUrl) : null,
        ]);
    }

    /**
     * Returns the UI payload for a single step, built from the posted draft step rather than
     * anything persisted, so a step can be configured before the import plan is ever saved.
     */
    public function stepSettings(): JsonResponse
    {
        $importer = $this->draftImporter(requireType: false);
        $batchSize = $this->request->input('step.batchSize');
        $sourceError = BaseImporter::sourceError($importer?->source, resolveHost: false);

        return new JsonResponse(new StepUiPayload(
            ui: new ImportPlanStepUiViewModel(
                $importer,
                $this->importService,
                app(UiResolver::class),
                (bool) $this->request->craftUser()?->can('saveImportPlans'),
                $batchSize === null || $batchSize === '' ? null : (int) $batchSize,
            )->ui(),
            canMap: $this->hasDestination($importer) && $sourceError === null,
            // shown under the source field; a step whose source is yet to be entered isn't flagged
            sourceError: ! empty($importer->source) ? $sourceError : null,
        ));
    }

    /**
     * Returns whether a step knows what it's importing into, so it has destination columns to map onto.
     *
     * An element importer resolves its field layout from whatever it's importing into — an
     * entry type, a volume — so until that's chosen there's nothing to map onto.
     */
    private function hasDestination(?BaseImporter $importer): bool
    {
        return $importer !== null && (! $importer instanceof ElementImporter || ! empty($importer->fieldLayout));
    }

    /**
     * Suggests a step's data source as it's typed, for the Data Source field's text expander.
     */
    public function sourceSuggestions(SourceSuggestions $suggestions): JsonResponse
    {
        $data = $this->request->validate([
            'query' => ['required', 'string', 'max:1024'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        return new JsonResponse($suggestions->suggest($data['query'], (int) ($data['limit'] ?? 50)));
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
            'step.source' => ['nullable', 'string'],
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
     * Reads a draft step's data source for its first rows, so the source can be checked before
     * it's mapped.
     */
    public function sourcePreview(ImportPreview $preview): JsonResponse
    {
        $importer = $this->mappingImporter();

        abort_if($importer === null, 400, 'Choose an importer type first.');

        return new JsonResponse($preview->source($importer));
    }

    /**
     * Returns the mapping structure for a draft step: its destination columns, the source
     * columns read from its source, and a best guess at a mapping between the two.
     */
    public function stepMapping(): JsonResponse
    {
        $importer = $this->mappingImporter();

        // the panel's mapping button is hidden until this passes, so reaching here means the step
        // changed between the last refresh and the click
        if (! $this->hasDestination($importer)) {
            return $this->mappingUnavailable(t('Choose what this step imports into before mapping its data.'));
        }

        // validate the data source (which can be a URL too)
        $sourceError = BaseImporter::sourceError($importer->source, resolveHost: false);

        if ($sourceError !== null) {
            return $this->mappingUnavailable($sourceError, 'source');
        }

        // the file is only downloaded (if it's a URL) and parsed once the mapping is asked for
        try {
            $sourceDataCols = $importer->getSourceDataCols();
        } catch (Exception $e) {
            return $this->mappingUnavailable($e->getMessage(), 'source');
        }

        if ($sourceDataCols === null) {
            return $this->mappingUnavailable(t('The data in “{source}” couldn’t be read.', ['source' => $importer->source]), 'source');
        }

        $destinationCols = $importer->getDestinationCols();

        return $this->asJsonSuccess(null, new StepMappingPayload(
            available: true,
            destinationCols: $destinationCols,
            sourceDataCols: $sourceDataCols,
            values: MappingValues::fromImporter($importer),
            suggestions: ImportHelper::suggestMapValues($destinationCols, $sourceDataCols, $importer->map, $importer),
            templateTextExpanderTriggers: ImportHelper::templateTextExpanderTriggers($sourceDataCols),
            targetName: is_subclass_of($importer::targetClass(), ElementInterface::class)
                ? $importer::targetClass()::pluralLowerDisplayName()
                : null,
        )->jsonSerialize());
    }

    /**
     * Lists the existing elements a draft step's matching would update — the first ten found
     * by going through its source's rows in order — without importing anything.
     */
    public function testMatch(): JsonResponse
    {
        $importer = $this->mappingImporter();

        if (! $importer instanceof ElementImporter || ! $this->hasDestination($importer)) {
            return $this->asJsonFailure(t('Choose what this step imports into before testing its matching.'));
        }

        if (($sourceError = BaseImporter::sourceError($importer->source, resolveHost: false)) !== null) {
            return $this->asJsonFailure($sourceError);
        }

        $importer->prepareForImport();

        try {
            $items = $importer->withLocalFile(fn (string $filePath) => array_values($this->importService->getFormattedData($filePath)));
        } catch (Exception $e) {
            return $this->asJsonFailure($e->getMessage());
        }

        $found = $this->importService->findMatches($importer, $items);

        return new JsonResponse([
            'matches' => array_map(fn (array $match): array => [
                'row' => $match['row'],
                'id' => $match['element']->id,
                'label' => $match['element']->getUiLabel(),
                'url' => $match['element']->getCpEditUrl(),
            ], $found['matches']),
            'checked' => $found['checked'],
            'total' => count($items),
        ]);
    }

    /**
     * Returns the response for a step whose data can't be mapped, naming the step attribute
     * the message is about, if it's about one.
     */
    private function mappingUnavailable(string $message, ?string $attribute = null): JsonResponse
    {
        return $this->asJsonSuccess(null, new StepMappingPayload(
            available: false,
            message: $message,
            attribute: $attribute,
        )->jsonSerialize());
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
        $importer = $this->mappingImporter();

        abort_if($importer === null, 400, 'The import step couldn’t be built.');

        $fieldUid = $this->request->input('fieldUid');
        $field = ! empty($fieldUid) ? $this->fieldsService->getFieldByUid($fieldUid) : null;

        $fieldHandle = $this->request->input('fieldHandle');
        $fieldIsProperty = $this->request->boolean('fieldIsProperty');

        $fieldName = $field instanceof FieldInterface ? $field->name : $fieldHandle;
        $groups = [];

        if ($field instanceof ImportableElementContainerFieldInterface) {
            foreach ($field->getFieldLayoutProviders() as $provider) {
                $groups[] = new MappingColumnGroup(
                    providerName: $provider instanceof Chippable ? $provider->getUiLabel() : $provider->getHandle(),
                    destinationCols: ImportHelper::getDestinationColsForFieldLayout(
                        $provider->getFieldLayout(),
                        $field,
                        $provider,
                        $fieldHandle,
                    ),
                );
            }
        }

        // a container property gets its columns from its element type
        $targetClass = $importer::targetClass();
        if (! $field && $fieldIsProperty && is_subclass_of($targetClass, ImportableContainerPropertiesInterface::class)) {
            $groups[] = new MappingColumnGroup(
                providerName: null,
                destinationCols: $targetClass::getDestinationColsForProperty($importer, $fieldHandle) ?? [],
            );
        }

        // the source columns only feed the suggestions, so there are none if the file can't be read
        try {
            $sourceDataCols = $importer->getSourceDataCols() ?? [];
        } catch (Exception) {
            $sourceDataCols = [];
        }

        $allDestinationCols = array_merge(...array_map(fn (MappingColumnGroup $group) => $group->destinationCols, $groups));

        // the top-level columns are thrown in so a heading that exactly matches one of them
        // isn't also guessed at in here, then the guesses are narrowed back down to this panel
        $suggestions = ImportHelper::suggestMapValues(
            array_merge($importer->getDestinationCols(), $allDestinationCols),
            $sourceDataCols,
            $importer->map,
            $importer,
        );
        $rootPath = implode('.', Arr::bracketsToArray((string) $fieldHandle));

        return $this->asJsonSuccess(null, new NestedMappingPayload(
            title: t('Edit map for {fieldName}', ['fieldName' => $fieldName]),
            fieldName: $fieldName,
            groups: $groups,
            sourceDataCols: $sourceDataCols,
            suggestions: self::subtree($suggestions, $rootPath),
            templateTextExpanderTriggers: ImportHelper::templateTextExpanderTriggers($sourceDataCols),
        )->jsonSerialize());
    }

    /**
     * Returns the importer whose source should be read for the mapping panels.
     *
     * Only users who can save import plans get the posted draft step. Anyone else gets the saved
     * step with the posted step’s UID, so they can’t make the server fetch a source of their choosing.
     */
    private function mappingImporter(): ?BaseImporter
    {
        if ($this->request->craftUser()?->can('saveImportPlans')) {
            return $this->draftImporter();
        }

        $stepUid = $this->request->validate([
            'step' => ['required', 'array'],
            'step.uid' => ['required', 'string', 'max:36'],
        ])['step']['uid'];

        foreach ($this->importsService->getAllImportPlans() as $importPlan) {
            foreach ($importPlan->steps ?? [] as $importer) {
                if ($importer->uid === $stepUid) {
                    return $importer;
                }
            }
        }

        abort(404, 'Import step not found.');
    }

    /**
     * Builds a transient importer from the posted draft step. Nothing is looked up in the
     * database, so this works for a step that has never been saved.
     */
    private function draftImporter(bool $requireType = true): ?BaseImporter
    {
        $data = $this->request->validate([
            'step' => ['required', 'array'],
            'step.uid' => ['nullable', 'string', 'max:36'],
            'step.type' => [$requireType ? 'required' : 'nullable', 'nullable', 'string', Rule::in($this->importService->getAllImporterTypes())],
            'step.source' => ['nullable', 'string'],
            'step.transformer' => ['nullable', 'string'],
            'step.batchSize' => ['nullable', 'integer'],
            'step.settings' => ['nullable', 'array'],
        ]);

        $step = $data['step'];

        if (empty($step['type'])) {
            return null;
        }

        $step['settings'] = ImportHelper::decodeRecursive($step['settings'] ?? []);

        return ImportPlans::createImporter($step);
    }

    /**
     * Returns a tree holding only `$path`'s branch of `$tree`, still rooted at the top level.
     *
     * @param  array<mixed>  $tree
     * @return array<mixed>
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

        if (! $this->importsService->duplicateImportPlan($importPlan)) {
            return $this->asFailure(t('Couldn’t duplicate import plan.'));
        }

        return $this->asSuccess(t('“{name}” duplicated.', [
            'name' => $importPlan->name,
        ]));
    }

    public function reorder(): Response
    {
        $uids = $this->request->validate([
            'uids' => ['required', 'array'],
            'uids.*' => [
                'string',
                Rule::exists(Table::IMPORT_PLANS, 'uid')->whereNull('dateDeleted'),
            ],
        ])['uids'];

        $this->importsService->reorderImportPlans($uids);

        return $this->asSuccess(t('New order saved.'));
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

        abort_if(is_null($importPlan), 404, 'Import plan not found.');

        if ($this->importService->isRunning($importPlan)) {
            return $this->asFailure(t('This import plan is already running.'));
        }

        try {
            $dispatched = $this->importService->dispatchImport($importPlan);
        } catch (Throwable $e) {
            ImportLog::warning("Import failed: {$e->getMessage()}");

            $dispatched = false;
        }

        if (! $dispatched) {
            return $this->asFailure(t('Import could not be started.'));
        }

        return $this->asSuccess(t('Import started.'));
    }

    /** Starts each of the selected import plans that isn’t already running. */
    public function bulkRun(): Response
    {
        $started = 0;
        $skipped = [];

        foreach ($this->selectedPlans() as $importPlan) {
            try {
                $dispatched = ! $this->importService->isRunning($importPlan)
                    && $this->importService->dispatchImport($importPlan);
            } catch (Throwable $e) {
                ImportLog::warning("Import failed: {$e->getMessage()}");

                $dispatched = false;
            }

            if ($dispatched) {
                $started++;
            } else {
                $skipped[] = (string) $importPlan->name;
            }
        }

        if ($skipped !== []) {
            return $this->asFailure(t('{count, plural, =0{No imports were started} =1{# import started} other{# imports started}}; couldn’t start {names}.', [
                'count' => $started,
                'names' => implode(', ', $skipped),
            ]));
        }

        return $this->asSuccess(t('{count, plural, =1{# import started.} other{# imports started.}}', ['count' => $started]));
    }

    /** Duplicates each of the selected import plans. */
    public function bulkDuplicate(): Response
    {
        $plans = $this->selectedPlans(editable: true);

        foreach ($plans as $importPlan) {
            if (! $this->importsService->duplicateImportPlan($importPlan)) {
                return $this->asFailure(t('Couldn’t duplicate “{name}”.', ['name' => $importPlan->name]));
            }
        }

        return $this->asSuccess(t('{count, plural, =1{# import plan duplicated.} other{# import plans duplicated.}}', ['count' => count($plans)]));
    }

    /** Deletes each of the selected import plans. */
    public function bulkDestroy(): Response
    {
        $plans = $this->selectedPlans(editable: true);

        foreach ($plans as $importPlan) {
            $this->importsService->deleteImportPlan($importPlan);
        }

        return $this->asSuccess(t('{count, plural, =1{# import plan deleted.} other{# import plans deleted.}}', ['count' => count($plans)]));
    }

    /**
     * The import plans posted as `uids`, in the order they were posted.
     *
     * @return list<ImportPlanData>
     */
    private function selectedPlans(bool $editable = false): array
    {
        $uids = $this->request->validate([
            'uids' => ['required', 'array', 'min:1'],
            'uids.*' => ['required', 'string'],
        ])['uids'];

        return array_map(function (string $uid) use ($editable): ImportPlanData {
            $importPlan = $this->importsService->getImportPlanByUid($uid);

            abort_if(is_null($importPlan), 404, "Invalid import plan UID: $uid");
            abort_if($editable && ! $importPlan->isEditable(), 400, "This import plan is not editable via the control panel: $uid");

            return $importPlan;
        }, array_values($uids));
    }

    private function cpScreenResponse(ImportPlanData $importPlan): CpScreenResponse
    {
        $currentUser = $this->request->craftUser();
        $canSave = (bool) $currentUser?->can('saveImportPlans');
        $isNew = ! isset($importPlan->uid);

        return new CpScreenResponse()
            ->title($isNew ? t('Create a new import plan') : t('Edit {name} import plan', ['name' => $importPlan->name]))
            ->addCrumb(t('Import'), route('craft.cp.import.index'))
            ->formAttributes(['action' => action([self::class, 'store'])])
            ->inertiaPage('import/Edit', new ImportPlanEditViewModel(
                $importPlan,
                $this->importService,
                app(UiResolver::class),
                $canSave,
                canRun: ! $isNew && (bool) $currentUser?->can('triggerImportPlans'),
            ))
            ->when(
                $canSave,
                callback: function (CpScreenResponse $response) use ($importPlan, $currentUser) {
                    $response
                        ->action('import/save')
                        ->redirectUrl('import/{handle}');

                    if (! $importPlan->isEditable()) {
                        return;
                    }

                    if ($currentUser?->can('deleteImportPlans')) {
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
                    }
                },
            );
    }
}
