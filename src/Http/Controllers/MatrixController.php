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
            'namespace' => ['required'],
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
            'namespace' => ['required', 'string'],
        ]);

        /** @var Entry[] $entries */
        $entries = Entry::find()
            ->id($validated['entryIds'])
            ->fixedOrder()
            ->siteId($validated['siteId'])
            ->status(null)
            ->all();

        if (empty($entries)) {
            return new JsonResponse([
                'blockHtml' => '',
                'headHtml' => HtmlStack::headHtml(),
                'bodyHtml' => HtmlStack::bodyHtml(),
            ]);
        }

        $field = null;
        $entryTypes = null;
        $html = '';

        foreach ($entries as $entry) {
            $field ??= $entry->getField();

            abort_if(
                ! $field instanceof Matrix || $field->id !== $entry->fieldId,
                400,
                'Entry must belong to a Matrix field.',
            );

            $entryTypes ??= $field->getEntryTypesForField($entries, $entry->getOwner());

            Gate::authorize('view', $entry);

            $html .= InputNamespace::namespaceInputs(fn () => template('_components/fieldtypes/Matrix/block', [
                'name' => $field->handle,
                'entryTypes' => $entryTypes,
                'entry' => $entry,
                ...$field->blockFormVariables($entry, false),
            ]), $validated['namespace']);
        }

        return new JsonResponse([
            'blockHtml' => $html,
            'headHtml' => HtmlStack::headHtml(),
            'bodyHtml' => HtmlStack::bodyHtml(),
        ]);
    }
}
