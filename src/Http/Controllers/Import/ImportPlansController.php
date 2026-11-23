<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Import;

use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Cp\Data\NavItem;
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
use CraftCms\Cms\Import\Data\MappingColumnGroup;
use CraftCms\Cms\Import\Data\MappingValues;
use CraftCms\Cms\Import\Data\NestedMappingPayload;
use CraftCms\Cms\Import\Data\StepMappingPayload;
use CraftCms\Cms\Import\Data\StepSummary;
use CraftCms\Cms\Import\Data\StepUiPayload;
use CraftCms\Cms\Import\Enums\ImportRunStatus;
use CraftCms\Cms\Import\Enums\ImportRunTrigger;
use CraftCms\Cms\Import\Enums\ItemPreviewResult;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\ImportHelper;
use CraftCms\Cms\Import\ImportPlans;
use CraftCms\Cms\Import\ImportPreviews;
use CraftCms\Cms\Import\ImportRuns;
use CraftCms\Cms\Import\SourceSuggestions;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\ImportLog;
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
        private readonly ImportRuns $importRuns,
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
        $run = $this->importService->runState($importPlan, withFailures: false);

        return new ImportPlanIndexData(
            uid: $importPlan->uid,
            name: (string) $importPlan->name,
            handle: (string) $importPlan->handle,
            description: $importPlan->description,
            stepCount: count($importPlan->steps ?? []),
            steps: array_values(array_map(
                fn (BaseImporter $step): array => [
                    'uid' => $step->uid,
                    'label' => $step::displayName(),
                    'enabled' => $step->enabled,
                    'source' => $step->source,
                    'settings' => StepSummary::fromImporter($step)->settings,
                    'mappedFields' => count(array_filter($step->map, self::isMapped(...))),
                    // only rows a step matches on have a mode to follow
                    'matchMode' => $step instanceof ElementImporter && self::isMapped($step->matchCriteria)
                        ? $step->matchMode->value
                        : null,
                ],
                $importPlan->steps ?? [],
            )),
            editable: $editable,
            running: $running,
            progress: $running && $run !== null && $run['status'] === ImportRunStatus::Running->value ? $run['progress'] : null,
            run: $run,
            runsUrl: route('craft.cp.import.activity.index', ['plan' => $importPlan->handle]),
        );
    }

    /** Whether anything is mapped, or checked, at a mapping value or anywhere under it. */
    private static function isMapped(mixed $value): bool
    {
        return is_array($value)
            ? array_any($value, self::isMapped(...))
            : $value !== null && $value !== '' && $value !== false;
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
            'mappingUrl' => route('craft.cp.import.steps.mapping', ['handle' => $importPlan->handle, 'uid' => $uid]),
            'editable' => (bool) $this->request->craftUser()?->can('saveImportPlans'),
            'submit' => ['method' => 'post', 'url' => action([self::class, 'storeStep'])],
        ]);
    }

    /** One saved step's mapping on a page of its own, which its mapping slideout links to. */
    public function editStepMapping(string $handle, string $uid): InertiaResponse
    {
        abort_if(is_null($importPlan = $this->importsService->getImportPlanByHandle($handle)), 404, 'Import plan not found');
        abort_if(! $importPlan->isEditable(), 400, "This import plan is not editable: $importPlan->handle");

        $steps = $importPlan->serializeSteps() ?? [];
        $index = collect($steps)->search(fn (array $step) => $step['uid'] === $uid);

        abort_if($index === false, 404, 'Import step not found');

        $step = $steps[$index];
        $importer = ! empty($step['type']) ? ImportPlans::createImporter($step) : null;
        $stepUrl = route('craft.cp.import.steps.edit', ['handle' => $importPlan->handle, 'uid' => $uid]);

        return Inertia::render('import/StepMappingEdit', [
            'title' => t('Edit mapping'),
            'crumbs' => [
                new ActionItem()->label(t('Import'))->href(route('craft.cp.import.index')),
                new ActionItem()->label((string) $importPlan->name)->href(route('craft.cp.import.edit', ['handle' => $importPlan->handle])),
                new ActionItem()->label(t('Step {number}', ['number' => $index + 1]))->href($stepUrl),
            ],
            'planUid' => $importPlan->uid,
            'step' => $step,
            'mapping' => $this->mappingPayload($importer)->jsonSerialize(),
            'stepUrl' => $stepUrl,
            'editable' => (bool) $this->request->craftUser()?->can('saveImportPlans'),
            'submit' => ['method' => 'post', 'url' => action([self::class, 'storeStep'])],
        ]);
    }

    /**
     * Saves one step into its plan, replacing the step with its uid or adding it as new, then
     * goes back to the plan, or stays on the step's page to continue editing it.
     */
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
            redirect: $this->request->boolean('continueEditing')
                ? route('craft.cp.import.steps.edit', ['handle' => $importPlan->handle, 'uid' => $step['uid']])
                : route('craft.cp.import.edit', ['handle' => $importPlan->handle]),
        );
    }

    /** One saved step, as its slideouts take it, for opening it from outside its plan's page. */
    public function showStep(): JsonResponse
    {
        $data = $this->request->validate([
            'planUid' => ['required', 'string', 'max:36'],
            'uid' => ['required', 'string', 'max:36'],
        ]);

        abort_if(is_null($importPlan = $this->importsService->getImportPlanByUid($data['planUid'], true)), 404, 'Import plan not found');

        $step = collect($importPlan->serializeSteps() ?? [])->first(fn (array $step) => $step['uid'] === $data['uid']);

        abort_if(is_null($step), 404, 'Import step not found');

        return new JsonResponse(['step' => $step]);
    }

    /** Deletes one step from its plan, then goes back to the plan. */
    public function destroyStep(): Response
    {
        $data = $this->request->validate([
            'planUid' => ['required', 'string', 'max:36'],
            'uid' => ['required', 'string', 'max:36'],
        ]);

        abort_if(is_null($importPlan = $this->importsService->getImportPlanByUid($data['planUid'], true)), 400, "Invalid import plan UID: {$data['planUid']}");

        $steps = $importPlan->serializeSteps() ?? [];
        $remaining = array_values(array_filter($steps, fn (array $step) => $step['uid'] !== $data['uid']));

        abort_if(count($remaining) === count($steps), 404, 'Import step not found');

        $importPlan->steps($remaining);

        if (! $this->importsService->saveImportPlan($importPlan)) {
            return $this->asModelFailure($importPlan, t('Couldn’t delete import step.'), 'import');
        }

        return $this->asModelSuccess(
            $importPlan,
            t('Import step deleted.'),
            'import',
            redirect: route('craft.cp.import.edit', ['handle' => $importPlan->handle]),
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

        $run = $this->request->boolean('run');
        abort_if($run && ! $this->request->craftUser()?->can('triggerImportPlans'), 403);

        if (! $this->importsService->saveImportPlan($importPlan)) {
            return $this->asModelFailure($importPlan, t('Couldn’t save import plan.'), 'import');
        }

        return $this->asModelSuccess(
            $importPlan,
            $run ? $this->startAfterSaving($importPlan) : t('Import plan saved.'),
            'import',
            // the run's page shows how it's going
            redirect: $run ? ($this->latestRunUrl($importPlan) ?? route('craft.cp.import.edit', ['handle' => $importPlan->handle])) : null,
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
        return $this->asJsonSuccess(null, $this->mappingPayload($this->mappingImporter())->jsonSerialize());
    }

    /**
     * The columns a step maps between, or why it can't be mapped yet.
     */
    private function mappingPayload(?BaseImporter $importer): StepMappingPayload
    {
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

        return new StepMappingPayload(
            available: true,
            destinationCols: $destinationCols,
            sourceDataCols: $sourceDataCols,
            values: MappingValues::fromImporter($importer),
            suggestions: ImportHelper::suggestMapValues($destinationCols, $sourceDataCols, $importer->map, $importer),
            templateTextExpanderTriggers: ImportHelper::templateTextExpanderTriggers($sourceDataCols),
        );
    }

    /**
     * Where the import plan's latest run has got, overall and step by step, for its edit page to follow.
     */
    public function runState(): JsonResponse
    {
        ['handle' => $handle] = $this->request->validate(['handle' => ['required', 'string']]);
        $importPlan = $this->importsService->getImportPlanByHandle($handle);

        abort_if($importPlan === null, 404, 'Import plan not found.');

        return $this->asJsonSuccess(null, ['runState' => $this->importService->runState($importPlan)]);
    }

    /**
     * Previews what running a step would do to its items, a page of those with a result at a time, for the plan's
     * Preview section.
     */
    public function previewStep(ImportPreviews $previews): JsonResponse
    {
        $importer = $this->previewImporter();

        if (is_string($importer)) {
            return $this->asJsonSuccess(null, ['error' => $importer]);
        }

        $params = $this->request->validate([
            'page' => ['nullable', 'integer', 'min:0'],
            'result' => ['nullable', Rule::enum(ItemPreviewResult::class)],
            'previewableIndex' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $preview = $previews->previewStep(
                $importer,
                (int) ($params['page'] ?? 0),
                ItemPreviewResult::tryFrom((string) ($params['result'] ?? '')),
                previewableIndex: isset($params['previewableIndex']) ? (int) $params['previewableIndex'] : null,
            );
        } catch (Throwable $e) {
            return $this->asJsonSuccess(null, ['error' => $this->previewError($e)]);
        }

        $labels = $importer->getDestinationLabels();

        return $this->asJsonSuccess(null, [
            'error' => null,
            'total' => $preview['total'],
            'itemCount' => $preview['itemCount'],
            'counts' => $preview['counts'],
            'page' => $preview['page'],
            'pageSize' => ImportPreviews::PAGE_SIZE,
            'previewable' => $preview['previewable'],
            'previewableBefore' => $preview['previewableBefore'],
            'matchColumns' => array_map(fn (string $key): array => [
                'key' => $key,
                'label' => $labels[$key]['label'] ?? $key,
            ], $preview['matchKeys']),
            'items' => $preview['items'],
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
            'title' => ImportPreviews::itemTitle($preview->element),
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
    private function mappingUnavailable(string $message, ?string $attribute = null): StepMappingPayload
    {
        return new StepMappingPayload(
            available: false,
            message: $message,
            attribute: $attribute,
        );
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
        $importPlans = $this->editableImportPlansFromRequest('duplicated');
        $failed = array_filter($importPlans, fn (ImportPlanData $importPlan) => ! $this->importsService->duplicateImportPlan($importPlan));

        if ($failed !== []) {
            return $this->asFailure(count($importPlans) === 1
                ? t('Couldn’t duplicate import plan.')
                : t('Couldn’t duplicate {names}.', ['names' => implode(', ', array_map(fn (ImportPlanData $plan) => "“{$plan->name}”", $failed))]));
        }

        return $this->asSuccess(count($importPlans) === 1
            ? t('“{name}” duplicated.', ['name' => $importPlans[0]->name])
            : t('{count, number} import plans duplicated.', ['count' => count($importPlans)]));
    }

    /**
     * Returns the editable import plans whose uids were posted, as `uid` or `uids`, aborting if any isn't
     * one, so that none of them is acted on. `$action` names what's being done to them (e.g. “deleted”),
     * for the error message.
     *
     * @return non-empty-list<ImportPlanData>
     */
    private function editableImportPlansFromRequest(string $action): array
    {
        $uids = array_values(array_filter((array) ($this->request->input('uids') ?? $this->request->input('uid')), is_string(...)));

        if ($uids === []) {
            throw ValidationException::withMessages([
                'id' => t('uid is required.'),
            ]);
        }

        return array_map(function (string $uid) use ($action): ImportPlanData {
            $importPlan = $this->importsService->getImportPlanByUid($uid);

            abort_if(is_null($importPlan), 404, "Invalid import plan UID: $uid");
            abort_if(! $importPlan->isEditable(), 400, "This import plan is not editable, so it can’t be $action via the Control Panel: $uid");

            return $importPlan;
        }, $uids);
    }

    public function destroy(): Response
    {
        $importPlans = $this->editableImportPlansFromRequest('deleted');

        foreach ($importPlans as $importPlan) {
            $this->importsService->deleteImportPlan($importPlan);
        }

        return $this->asSuccess(count($importPlans) === 1
            ? t('“{name}” deleted.', ['name' => $importPlans[0]->name])
            : t('{count, number} import plans deleted.', ['count' => count($importPlans)]));
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
            $dispatched = $this->importService->dispatchImport($importPlan, trigger: ImportRunTrigger::ControlPanel);
        } catch (Throwable $e) {
            ImportLog::warning("Import failed: {$e->getMessage()}");

            $dispatched = false;
        }

        if (! $dispatched) {
            return $this->asFailure(t('Import could not be started.'));
        }

        // the run's page shows how it's going, unless it was asked to run in the background
        if (! $this->request->boolean('background')) {
            return $this->asSuccess(t('Import started.'), redirect: $this->latestRunUrl($importPlan));
        }

        // a page that can follow the run swaps this message for its progress
        return $this->asSuccess(
            t('Import started.'),
            notificationSettings: ['importPlan' => ['handle' => $importPlan->handle, 'name' => (string) $importPlan->name]],
            target: 'import-runs',
        );
    }

    /** The page of the plan's latest run, to follow one that was just started. */
    private function latestRunUrl(ImportPlanData $importPlan): ?string
    {
        $run = $this->importRuns->latest($importPlan);

        return $run !== null ? route('craft.cp.import.activity.show', ['runId' => $run->uid]) : null;
    }

    /**
     * Starts a run of a plan that was just saved, and says how both went.
     */
    private function startAfterSaving(ImportPlanData $importPlan): string
    {
        if ($this->importService->isRunning($importPlan)) {
            return t('Import plan saved. It’s already running, so it wasn’t started again.');
        }

        try {
            $dispatched = $this->importService->dispatchImport($importPlan, trigger: ImportRunTrigger::ControlPanel);
        } catch (Throwable $e) {
            ImportLog::warning("Import failed: {$e->getMessage()}");

            $dispatched = false;
        }

        return $dispatched
            ? t('Import plan saved and started.')
            : t('Import plan saved, but it couldn’t be started.');
    }

    /**
     * Every import plan, to move between them without going back to the index.
     *
     * @return list<NavItem>
     */
    private function planNav(ImportPlanData $importPlan): array
    {
        $saved = $importPlan->uid !== null || ! $importPlan->isEditable();

        return $this->importsService->getAllImportPlans()
            ->sortBy(fn (ImportPlanData $plan) => (string) $plan->name, SORT_NATURAL | SORT_FLAG_CASE)
            ->map(fn (ImportPlanData $plan) => new NavItem()
                ->label((string) $plan->name)
                ->href(route('craft.cp.import.edit', ['handle' => $plan->handle]))
                ->selected($saved && $plan->handle === $importPlan->handle))
            ->values()
            ->all();
    }

    private function cpScreenResponse(ImportPlanData $importPlan): CpScreenResponse
    {
        $currentUser = $this->request->craftUser();
        // a plan registered in code is shown as it is, with nothing to save
        $canSave = $importPlan->isEditable() && $currentUser?->can('saveImportPlans');

        return new CpScreenResponse()
            ->title(match (true) {
                ! $importPlan->isEditable() => (string) $importPlan->name,
                ! isset($importPlan->uid) => t('Create a new import plan'),
                default => t('Edit {name} import plan', ['name' => $importPlan->name]),
            })
            ->addCrumb(t('Import'), route('craft.cp.import.index'))
            ->subnav($this->planNav($importPlan))
            ->formAttributes(['action' => action([self::class, 'store'])])
            ->inertiaPage('import/Edit', new ImportPlanEditViewModel(
                $importPlan,
                $this->importService,
                app(UiResolver::class),
                $canSave,
                (bool) $currentUser?->can('triggerImportPlans'),
                $canSave && $importPlan->uid && $importPlan->isEditable() && $currentUser?->can('deleteImportPlans') ? [
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
                ] : null,
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

                    if ($currentUser?->can('triggerImportPlans')) {
                        $response->addAltAction(t('Run this import plan'), [
                            'action' => [
                                'type' => 'http',
                                'method' => 'POST',
                                'url' => action([self::class, 'run']),
                                'body' => ['uid' => $importPlan->uid],
                                'confirm' => t('Are you sure you want to run “{name}”?', [
                                    'name' => $importPlan->name,
                                ]),
                            ],
                        ]);
                        $response->addAltAction(t('Run in the background'), [
                            'action' => [
                                'type' => 'http',
                                'method' => 'POST',
                                'url' => action([self::class, 'run']),
                                'body' => ['uid' => $importPlan->uid, 'background' => true],
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
