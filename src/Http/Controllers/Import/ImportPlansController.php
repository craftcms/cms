<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Import;

use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ImportableContainerPropertiesInterface;
use CraftCms\Cms\Element\Data\FieldComparisonRow;
use CraftCms\Cms\Element\FieldChangeComparisons;
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
use CraftCms\Cms\Import\Data\ItemPreview;
use CraftCms\Cms\Import\Data\MappingColumnGroup;
use CraftCms\Cms\Import\Data\MappingValues;
use CraftCms\Cms\Import\Data\NestedMappingPayload;
use CraftCms\Cms\Import\Data\StepMappingPayload;
use CraftCms\Cms\Import\Data\StepSummary;
use CraftCms\Cms\Import\Data\StepUiPayload;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\ImportHelper;
use CraftCms\Cms\Import\ImportPlans;
use CraftCms\Cms\Import\ImportPreviews;
use CraftCms\Cms\Import\SourceSuggestions;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Json as JsonSupport;
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
        return new ImportPlanIndexData(
            uid: $importPlan->uid,
            name: (string) $importPlan->name,
            handle: (string) $importPlan->handle,
            description: $importPlan->description,
            stepCount: count($importPlan->steps ?? []),
            stepLabels: array_values(array_map(
                fn (BaseImporter $step): string => $step::displayName(),
                $importPlan->steps ?? [],
            )),
            editable: $editable,
        );
    }

    public function create(): CpScreenResponse
    {
        $old = $this->request->session()->get('import');

        return $this->cpScreenResponse(! empty($old) ? new ImportPlanData($old) : new ImportPlanData);
    }

    public function edit(?string $handle = null): CpScreenResponse
    {
        $handle ??= $this->request->input('handle');

        if (is_null($handle)) {
            return $this->create();
        }

        abort_if(is_null($found = $this->importsService->getImportPlanByHandle($handle)), 404, 'Import plan not found');
        abort_if(! $found->isEditable(), 400, "This import plan is not editable: $found->handle");

        $old = $this->request->session()->get('import');

        return $this->cpScreenResponse(! empty($old) ? new ImportPlanData($old) : $found);
    }

    /** One saved step on a page of its own, which a step's slideout links to. */
    public function editStep(string $handle, string $uid): InertiaResponse
    {
        abort_if(is_null($importPlan = $this->importsService->getImportPlanByHandle($handle)), 404, 'Import plan not found');
        abort_if(! $importPlan->isEditable(), 400, "This import plan is not editable: $importPlan->handle");

        $steps = $importPlan->serializeSteps() ?? [];
        $index = collect($steps)->search(fn (array $step) => $step['uid'] === $uid);

        abort_if($index === false, 404, 'Import step not found');

        $step = $steps[$index];
        $planUrl = route('craft.cp.import.edit', ['handle' => $importPlan->handle]);
        $importer = ! empty($step['type']) ? ImportPlans::createImporter($step) : null;

        return Inertia::render('import/StepEdit', [
            'title' => t('Edit step {number} of {name}', ['number' => $index + 1, 'name' => $importPlan->name]),
            'crumbs' => [
                new ActionItem()->label(t('Import'))->href(route('craft.cp.import.index')),
                new ActionItem()->label((string) $importPlan->name)->href($planUrl),
            ],
            'planUid' => $importPlan->uid,
            'step' => $step,
            'stepUi' => $this->stepUiPayload($importer, $step['batchSize'] ?? null),
            'editable' => (bool) $this->request->craftUser()?->can('saveImportPlans'),
            'submit' => ['method' => 'post', 'url' => action([self::class, 'storeStep'])],
        ]);
    }

    /** Saves one step into its plan, replacing the step with its uid or adding it as new. */
    public function storeStep(): Response
    {
        $data = $this->request->validate([
            'planUid' => ['required', 'string', 'max:36'],
            'step' => ['required', 'array'],
            'step.uid' => ['required', 'string', 'max:36'],
        ]);

        abort_if(is_null($importPlan = $this->importsService->getImportPlanByUid($data['planUid'], true)), 400, "Invalid import plan UID: {$data['planUid']}");

        // the validated data keeps only the step keys named in the rules
        $step = $this->request->input('step');
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

        return $this->asModelSuccess(
            $importPlan,
            t('Import step saved.'),
            'import',
            redirect: route('craft.cp.import.steps.edit', ['handle' => $importPlan->handle, 'uid' => $step['uid']]),
        );
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
     * Returns the UI payload for a single step, built from the posted draft step rather than
     * anything persisted, so a step can be configured before the import plan is ever saved.
     */
    public function stepSettings(): JsonResponse
    {
        $batchSize = $this->request->input('step.batchSize');

        return new JsonResponse($this->stepUiPayload(
            $this->draftImporter(requireType: false),
            $batchSize === null || $batchSize === '' ? null : (int) $batchSize,
        ));
    }

    private function stepUiPayload(?BaseImporter $importer, ?int $batchSize): StepUiPayload
    {
        $sourceError = BaseImporter::sourceError($importer?->source, resolveHost: false)
            ?? BaseImporter::dataTypeError($importer?->source, $importer?->dataType);

        return new StepUiPayload(
            ui: new ImportPlanStepUiViewModel(
                $importer,
                $this->importService,
                app(UiResolver::class),
                (bool) $this->request->craftUser()?->can('saveImportPlans'),
                $batchSize,
            )->ui(),
            canMap: $this->hasDestination($importer) && $sourceError === null,
            // shown under the source field; a step whose source is yet to be entered isn't flagged
            sourceError: ! empty($importer->source) ? $sourceError : null,
            summary: StepSummary::fromImporter($importer),
        );
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
        $errors = ImportPlanData::stepErrors($this->validatedDraftStep(requireType: false));

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return new JsonResponse(['success' => true]);
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
        )->jsonSerialize());
    }

    /**
     * Previews what running a step would do to its first items, for the plan's Preview section.
     */
    public function previewStep(ImportPreviews $previews): JsonResponse
    {
        $importer = $this->previewImporter();

        if (is_string($importer)) {
            return $this->asJsonSuccess(null, ['error' => $importer]);
        }

        try {
            $preview = $previews->previewStep($importer);
        } catch (Throwable $e) {
            return $this->asJsonSuccess(null, ['error' => $this->previewError($e)]);
        }

        $labels = $importer->getDestinationLabels();

        return $this->asJsonSuccess(null, [
            'error' => null,
            'total' => $preview['total'],
            'matchColumns' => array_map(fn (string $key): array => [
                'key' => $key,
                'label' => $labels[$key]['label'] ?? $key,
            ], $preview['matchKeys']),
            'items' => array_map(fn (int $index, ItemPreview $item): array => [
                'index' => $index,
                'result' => $item->result->value,
                'title' => $item->element?->getUiLabel(),
                'match' => array_map(
                    fn (mixed $value): string => is_scalar($value) ? (string) $value : (string) JsonSupport::encode($value),
                    Arr::only($item->matchCriteria, $preview['matchKeys']),
                ),
                'errors' => $item->errors,
            ], array_keys($preview['items']), $preview['items']),
        ]);
    }

    /**
     * Shows how running a step would change one of its items' elements, as the Compare screen shows two versions of
     * one: the element as it is now, or a blank one when the item would create it, beside the element after the import.
     */
    public function previewItem(ImportPreviews $previews, FieldChangeComparisons $comparisons): JsonResponse
    {
        $index = (int) $this->request->validate(['index' => ['required', 'integer', 'min:0']])['index'];
        $importer = $this->previewImporter();

        if (is_string($importer)) {
            return $this->asJsonSuccess(null, ['error' => $importer]);
        }

        try {
            $preview = $previews->previewItem($importer, $index);
        } catch (Throwable $e) {
            return $this->asJsonSuccess(null, ['error' => $this->previewError($e)]);
        }

        abort_if($preview === null, 404, 'Import item not found.');

        $payload = [
            'error' => null,
            'result' => $preview->result->value,
            'title' => $preview->element?->getUiLabel(),
            'errors' => $preview->errors,
            'comparison' => null,
        ];
        $element = $preview->element;

        if ($element === null) {
            return $this->asJsonSuccess(null, $payload);
        }

        $existing = $element->id
            ? $element::find()->id($element->id)->siteId($element->siteId)->drafts(null)->status(null)->one()
            : null;

        if ($existing !== null && ! $this->request->craftUser()?->can('view', $existing)) {
            return $this->asJsonSuccess(null, [
                ...$payload,
                'error' => t('You don’t have permission to view the element this item matches.'),
            ]);
        }

        $blankData = [];
        $from = $existing ?? $importer->prepareNewRootElementForImport($blankData);
        // the import makes the changes
        $comparison = $comparisons->compareElements($from, $element, ['from' => false, 'to' => true], [
            'to' => ElementImporter::errorsByField($element),
        ]);
        $side = fn (string $label, mixed $ui): array => [
            'label' => $label,
            'edited' => false,
            'siteName' => null,
            'versionMenu' => [],
            'siteMenu' => [],
            'ui' => $ui,
        ];

        return $this->asJsonSuccess(null, [
            ...$payload,
            'comparison' => [
                'from' => $side($existing ? t('Current') : t('Blank'), $comparison['from']),
                'to' => $side(t('After the import'), $comparison['to']),
                'rows' => array_map(fn (FieldComparisonRow $row): array => $row->toArray(), $comparison['rows']),
                'badged' => $comparison['badged'],
                'editUrl' => $existing?->getCpEditUrl(),
            ],
        ]);
    }

    /**
     * The posted step's importer, when it's one that can be previewed, or else why it can't be.
     */
    private function previewImporter(): ElementImporter|string
    {
        $importer = $this->mappingImporter();

        if (! $importer instanceof ElementImporter) {
            return t('Only steps that import elements can be previewed.');
        }

        if (! $this->hasDestination($importer)) {
            return t('Choose what this step imports into to preview it.');
        }

        return BaseImporter::sourceError($importer->source, resolveHost: false) ?? $importer;
    }

    private function previewError(Throwable $e): string
    {
        return $e instanceof ValidationException
            ? (Arr::first(Arr::flatten($e->errors())) ?? $e->getMessage())
            : $e->getMessage();
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
        $step = $this->validatedDraftStep($requireType);

        if (empty($step['type'])) {
            return null;
        }

        return ImportPlans::createImporter($step);
    }

    /**
     * Validates the posted draft step and returns it with its settings decoded.
     *
     * @return array<string, mixed>
     */
    private function validatedDraftStep(bool $requireType): array
    {
        $step = $this->request->validate([
            'step' => ['required', 'array'],
            'step.uid' => ['nullable', 'string', 'max:36'],
            'step.type' => [$requireType ? 'required' : 'nullable', 'string', Rule::in($this->importService->getAllImporterTypes())],
            'step.source' => ['nullable', 'string'],
            'step.transformer' => ['nullable', 'string'],
            'step.batchSize' => ['nullable', 'integer'],
            'step.settings' => ['nullable', 'array'],
        ])['step'];

        $step['settings'] = ImportHelper::decodeRecursive($step['settings'] ?? []);

        return $step;
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
        $importPlan = $this->editableImportPlanFromRequest('duplicated');

        if (! $this->importsService->duplicateImportPlan($importPlan)) {
            return $this->asFailure(t('Couldn’t duplicate import plan.'));
        }

        return $this->asSuccess(t('“{name}” duplicated.', [
            'name' => $importPlan->name,
        ]));
    }

    /**
     * Returns the editable import plan whose uid was posted, aborting if there isn't one.
     * `$action` names what's being done to the plan (e.g. “deleted”), for the error message.
     */
    private function editableImportPlanFromRequest(string $action): ImportPlanData
    {
        $uid = $this->request->input('uid');

        if (! $uid) {
            throw ValidationException::withMessages([
                'id' => t('uid is required.'),
            ]);
        }

        $importPlan = $this->importsService->getImportPlanByUid($uid);

        abort_if(is_null($importPlan), 404, "Invalid import plan UID: $uid");
        abort_if(! $importPlan->isEditable(), 400, "This import plan is not editable, so it can’t be $action via the Control Panel: $uid");

        return $importPlan;
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
        $importPlan = $this->editableImportPlanFromRequest('deleted');

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

    private function cpScreenResponse(ImportPlanData $importPlan): CpScreenResponse
    {
        $currentUser = $this->request->craftUser();
        $canSave = (bool) $currentUser?->can('saveImportPlans');

        return new CpScreenResponse()
            ->title(! isset($importPlan->uid) ? t('Create a new import plan') : t('Edit {name} import plan', ['name' => $importPlan->name]))
            ->addCrumb(t('Import'), route('craft.cp.import.index'))
            ->formAttributes(['action' => action([self::class, 'store'])])
            ->inertiaPage('import/Edit', new ImportPlanEditViewModel(
                $importPlan,
                $this->importService,
                app(UiResolver::class),
                $canSave,
            ))
            ->when(
                $canSave,
                callback: function (CpScreenResponse $response) use ($importPlan, $currentUser) {
                    $response
                        ->action('import/save')
                        ->redirectUrl('import')
                        ->saveShortcutRedirectUrl('import/{handle}')
                        ->addAltAction(t('Save and continue editing'), [
                            'redirect' => 'import/{handle}',
                            'shortcut' => true,
                            'retainScroll' => true,
                        ]);

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

                    if ($currentUser?->can('triggerImportPlans')) {
                        $response->addAltAction(t('Run this import plan'), [
                            'action' => [
                                'type' => 'http',
                                'method' => 'POST',
                                'url' => action([self::class, 'run']),
                                'body' => [
                                    'uid' => $importPlan->uid,
                                    'redirect' => Crypt::encrypt(route('craft.cp.import.edit', ['handle' => $importPlan->handle])),
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
