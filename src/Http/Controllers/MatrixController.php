<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Element\Validation\Rules\ElementTypeRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\NestedFormPayload;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

readonly class MatrixController
{
    use RespondsWithFlash;

    public function __construct(
        private Drafts $drafts,
        private Elements $elements,
        private EntryTypes $entryTypes,
        private Sites $sites,
    ) {}

    public function defaultTableColumnOptions(Request $request): JsonResponse
    {
        $request->validate([
            'entryTypeIds' => ['required', 'array'],
            'entryTypeIds.*' => ['integer'],
        ]);

        $entryTypes = collect($request->array('entryTypeIds'))->map(function (mixed $entryTypeId) {
            $entryType = $this->entryTypes->getEntryTypeById((int) $entryTypeId);

            abort_if(is_null($entryType), 400, "Invalid entry type ID: $entryTypeId");

            return $entryType;
        })->all();

        return new JsonResponse([
            'options' => Matrix::defaultTableColumnOptions($entryTypes),
        ]);
    }

    public function createEntry(Request $request): Response
    {
        $validated = $request->validate([
            'fieldId' => ['required'],
            'entryTypeId' => ['required'],
            'ownerId' => ['required'],
            'ownerElementType' => ['required', 'string', new ElementTypeRule],
            'siteId' => ['required'],
            // The legacy stack asks for rendered block HTML and passes the input
            // namespace it wants that HTML written under. Form controls pass the
            // control's `path` instead and get form nodes back. See the response
            // at the bottom of this method; the HTML half goes when the Twig
            // block renderer does.
            'namespace' => ['required_without:path'],
            'path' => ['required_without:namespace', 'array'],
            'path.*' => ['string'],
            'staticEntries' => ['nullable', 'boolean'],
            'duplicate' => ['nullable'],
        ]);

        $owner = $this->owner(
            (int) $validated['ownerId'],
            $validated['ownerElementType'],
            (int) $validated['siteId'],
        );

        abort_if(is_null($owner), 400, 'Invalid owner ID, element type, or site ID.');

        $field = $owner->getFieldLayout()?->getFieldById($validated['fieldId']);

        abort_if(! $field instanceof Matrix, 400, "Invalid Matrix field ID: $validated[fieldId]");

        $entryType = $this->entryTypes->getEntryTypeById($validated['entryTypeId']);

        abort_if(is_null($entryType), 400, "Invalid entry type ID: $validated[entryTypeId]");

        // Any entry type would save, and then the field couldn't render what it
        // got back — `Form\Controls\Matrix` rejects a block whose type it doesn't
        // offer, which takes the whole edit screen down with it.
        abort_if(
            ! in_array($entryType->id, array_column($field->getEntryTypes(), 'id'), true),
            400,
            "Entry type $validated[entryTypeId] is not available to Matrix field $validated[fieldId].",
        );

        $site = $this->sites->getSiteById($validated['siteId'], true);

        abort_if(is_null($site), 400, "Invalid site ID: $validated[siteId]");

        $attributes = [
            'siteId' => $validated['siteId'],
            'uid' => Str::uuid()->toString(),
            'typeId' => $entryType->id,
            'fieldId' => $validated['fieldId'],
            'primaryOwner' => $owner,
            'owner' => $owner,
            'slug' => ElementHelper::tempSlug(),
        ];

        // duplicate an existing entry?
        $sourceId = $validated['duplicate'] ?? null;
        if ($sourceId) {
            /** @var ?Entry $source */
            $source = Entry::find()
                ->id($sourceId)
                ->siteId($validated['siteId'])
                ->fieldId($validated['fieldId'])
                ->ownerId($validated['ownerId'])
                ->typeId($validated['entryTypeId'])
                ->drafts(null)
                ->status(null)
                ->one();

            abort_if(is_null($source), 400, "Invalid source element ID: $sourceId");

            Gate::authorize('view', $source);

            // set owner so that the canDuplicateAsDraft checks the max entries on the right owner and not only the canonical
            $source->setOwner($owner);

            Gate::authorize('duplicateAsDraft', $source);

            try {
                $entry = $this->elements->duplicateElement($source, [
                    ...$attributes,
                    'isProvisionalDraft' => false,
                    'draftId' => null,
                    'sortOrder' => null,
                ]);
            } catch (InvalidElementException) {
                return $this->asFailure(t('Couldn’t duplicate {type}.', [
                    'type' => Entry::lowerDisplayName(),
                ]));
            }
        } else {
            $entry = new Entry([
                ...$attributes,
            ]);

            Gate::authorize('save', $entry);

            $entry->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);

            if (! $this->drafts->saveElementAsDraft($entry, $request->craftUser()?->getCraftUserId(), markAsSaved: false)) {
                return $this->asFailure(mb_ucfirst(t('Couldn’t create {type}.', [
                    'type' => Entry::lowerDisplayName(),
                ])));
            }
        }

        /** @var EntryQuery<Entry>|ElementCollection<array-key, Entry> $value */
        $value = $owner->getFieldValue($field->handle);

        /** @var Entry[] $entries */
        $entries = $value->all();

        if (isset($validated['path'])) {
            return new JsonResponse($this->blockFormResponse($entry, $validated['path']));
        }

        $html = InputNamespace::namespaceInputs(fn () => template('_components/fieldtypes/Matrix/block', [
            'name' => $field->handle,
            'entryTypes' => $field->getEntryTypesForField($entries, $owner),
            'entry' => $entry,
            'isFresh' => true,
            'staticEntries' => $validated['staticEntries'] ?? false,
            ...$field->blockFormVariables($entry, false),
        ]), $validated['namespace']);

        return new JsonResponse([
            'blockHtml' => $html,
            'headHtml' => HtmlStack::headHtml(),
            'bodyHtml' => HtmlStack::bodyHtml(),
        ]);
    }

    /**
     * The new block as form nodes, in the same shape the Matrix Control ships its
     * blocks in — so the browser renders it with FormNodeList like anything else,
     * rather than splicing in server-rendered HTML.
     *
     * @param  list<string>  $path  The Matrix Control's path, e.g. `['fields', 'pageBuilder']`
     * @return array{uid: string, type: string, form: array<string, mixed>, values: array<string, mixed>}
     */
    private function blockFormResponse(Entry $entry, array $path): array
    {
        $scope = [...$path, 'entries', $entry->uid];
        $payload = app(FieldLayoutCompiler::class)->compile(
            $entry->getFieldLayout(),
            $entry,
            new FormContext(namespace: $scope, refreshable: true),
        );

        return [
            'uid' => $entry->uid,
            'type' => $entry->getType()->handle,
            'form' => new NestedFormPayload(
                scope: $scope,
                refreshable: true,
                nodes: $payload->nodes,
            )->jsonSerialize(),
            'values' => $payload->values,
        ];
    }

    /** @param class-string<ElementInterface> $elementType */
    private function owner(int $id, string $elementType, int $siteId): ?ElementInterface
    {
        return $this->elements->getElementById($id, $elementType, $siteId);
    }

    /**
     * Renders the blocks for newly-created entries.
     */
    public function renderBlocks(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entryIds' => ['required', 'array', 'min:1'],
            'siteId' => ['required'],
            // Same split as `createEntry()`: the legacy stack asks for rendered
            // HTML under an input namespace, a Form Control asks for form nodes
            // under its own path.
            'namespace' => ['required_without:path', 'string'],
            'path' => ['required_without:namespace', 'array'],
            'path.*' => ['string'],
        ]);

        /** @var Entry[] $entries */
        $entries = Entry::find()
            ->id($validated['entryIds'])
            ->fixedOrder()
            ->siteId($validated['siteId'])
            ->status(null)
            ->all();

        if (empty($entries)) {
            return isset($validated['path'])
                ? new JsonResponse(['blocks' => []])
                : new JsonResponse([
                    'blockHtml' => '',
                    'headHtml' => HtmlStack::headHtml(),
                    'bodyHtml' => HtmlStack::bodyHtml(),
                ]);
        }

        $field = null;
        $entryTypes = null;
        $html = '';

        $blocks = [];

        foreach ($entries as $entry) {
            $field ??= $entry->getField();

            abort_if(
                ! $field instanceof Matrix || $field->id !== $entry->fieldId,
                400,
                'Entry must belong to a Matrix field.',
            );

            // An entry of a type the field doesn't offer would render here and
            // then take the edit screen down on the next load, where the Matrix
            // Control rejects it. Same guard `createEntry()` applies.
            abort_if(
                ! in_array($entry->getType()->id, array_column($field->getEntryTypes(), 'id'), true),
                400,
                "Entry type {$entry->getType()->id} is not available to Matrix field {$field->id}.",
            );

            $entryTypes ??= $field->getEntryTypesForField($entries, $entry->getOwner());

            Gate::authorize('view', $entry);

            if (isset($validated['path'])) {
                $blocks[] = $this->blockFormResponse($entry, $validated['path']);

                continue;
            }

            $html .= InputNamespace::namespaceInputs(fn () => template('_components/fieldtypes/Matrix/block', [
                'name' => $field->handle,
                'entryTypes' => $entryTypes,
                'entry' => $entry,
                ...$field->blockFormVariables($entry, false),
            ]), $validated['namespace']);
        }

        if (isset($validated['path'])) {
            return new JsonResponse(['blocks' => $blocks]);
        }

        return new JsonResponse([
            'blockHtml' => $html,
            'headHtml' => HtmlStack::headHtml(),
            'bodyHtml' => HtmlStack::bodyHtml(),
        ]);
    }
}
