<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\ElementIndex;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\NestedElementManager;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Http\EmbeddedNestedElementScope;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class SaveElementIndexElementsController
{
    private ElementIndexRequest $request;

    use RespondsWithFlash;

    public function __construct(
        private Elements $elements,
    ) {}

    public function __invoke(ElementIndexRequest $request): Response
    {
        $this->request = $request;

        $elementType = $this->request->elementType();

        $this->request->validate([
            'siteId' => ['required', 'integer', 'min:1'],
            'namespace' => ['required', 'string'],
            $namespace = $this->request->input('namespace') => ['required', 'array'],
        ]);

        $data = $this->request->array($namespace);
        $originalIds = $this->elementIds($data);
        $embedded = $request->context() === ElementSources::CONTEXT_EMBEDDED_INDEX;

        if ($embedded) {
            DB::beginTransaction();
        }

        try {
            if ($embedded) {
                $nestedElementScope = new EmbeddedNestedElementScope($request);
                $owner = $nestedElementScope->owner();
                $preparedByOriginalId = $nestedElementScope->selectedElements($originalIds)
                    ->mapWithKeys(fn ($element) => [
                        $element->id => NestedElementManager::prepareElementForOwner($element, $owner),
                    ]);
                $elements = collect($originalIds)->map(fn (int $id) => $preparedByOriginalId[$id]);
            } else {
                $elements = $this->getElements(
                    elementType: $elementType,
                    siteId: $this->request->integer('siteId'),
                    data: $data,
                );
            }

            if ($elements->isEmpty()) {
                throw ValidationException::withMessages([
                    $namespace => 'No valid element IDs provided.',
                ]);
            }

            foreach ($elements as $element) {
                Gate::authorize('save', $element);
            }

            $errors = $this->validateElements($elements, $namespace, $data, $embedded ? $originalIds : null);

            if (! empty($errors)) {
                if ($embedded) {
                    DB::rollBack();
                }

                return new JsonResponse([
                    'errors' => $errors,
                ]);
            }

            $saveElements = function () use ($elements): void {
                foreach ($elements as $element) {
                    if (! $this->elements->saveElement($element)) {
                        Log::error("Couldn’t save element {$element->id}: ".implode(', ', $element->getFirstErrors()));
                        abort(500, "Couldn’t save element {$element->id}");
                    }
                }
            };

            if ($embedded) {
                $saveElements();
                DB::commit();
            } else {
                DB::transaction($saveElements);
            }
        } catch (\Throwable $exception) {
            if ($embedded && DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $exception;
        }

        return $this->asSuccess();
    }

    /**
     * @param  array<string, array<string, mixed>>  $data
     * @return list<int>
     */
    private function elementIds(array $data): array
    {
        return array_map(
            fn (string $key): int => (int) Str::chopStart($key, 'element-'),
            array_keys($data),
        );
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, array<string, mixed>>  $data
     * @return Collection<int, ElementInterface>
     */
    private function getElements(string $elementType, int $siteId, array $data): Collection
    {
        $elementIds = $this->elementIds($data);

        return $elementType::find()
            ->id($elementIds)
            ->status(null)
            ->drafts(null)
            ->provisionalDrafts(null)
            ->siteId($siteId)
            ->get();
    }

    /**
     * @param  Collection<int, ElementInterface>  $elements
     * @param  array<string, array<string, mixed>>  $data
     * @param  list<int>|null  $originalIds
     * @return array<int, array<string, array<int, string>>>
     */
    private function validateElements(Collection $elements, string $namespace, array $data, ?array $originalIds): array
    {
        $errors = [];

        foreach ($elements as $index => $element) {
            $postedElementKey = 'element-'.($originalIds[$index] ?? $element->id);
            $attributes = Arr::except($data[$postedElementKey] ?? [], 'fields');

            if ($originalIds !== null) {
                $attributes = Arr::only($attributes, $element->safeAttributes());
                $attributes = Arr::except($attributes, [
                    'fieldId',
                    'ownerId',
                    'primaryOwnerId',
                ]);
            }

            if ($element instanceof User) {
                $attributes = Arr::except($attributes, User::SENSITIVE_ATTRIBUTES);
            }

            if (! empty($attributes)) {
                $element->ruleset->withScenario(
                    ElementRules::SCENARIO_LIVE,
                    fn () => $element->setAttributesFromRequest($attributes),
                );
            }

            $element->setFieldValuesFromRequest("$namespace.$postedElementKey.fields");

            if ($originalIds !== null) {
                Gate::authorize('save', $element);
            }

            if ($element->getIsUnpublishedDraft()) {
                $element->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
            } elseif ($element->enabled && $element->getEnabledForSite()) {
                $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
            }

            $names = array_merge(
                array_keys($attributes),
                array_map(
                    fn (string $handle): string => "field:$handle",
                    array_keys($data[$postedElementKey]['fields'] ?? []),
                ),
            );

            if (! $element->validate($names)) {
                $errors[$originalIds[$index] ?? $element->getCanonicalId()] = $element->errors()->getMessages();
            }
        }

        return $errors;
    }
}
