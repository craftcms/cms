<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\ElementIndex;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Validation\ElementRules;
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

        $elements = $this->getElements(
            elementType: $elementType,
            siteId: $this->request->integer('siteId'),
            data: $data,
        );

        if ($elements->isEmpty()) {
            throw ValidationException::withMessages([
                $namespace => 'No valid element IDs provided.',
            ]);
        }

        foreach ($elements as $element) {
            Gate::authorize('save', $element);
        }

        $errors = $this->validateElements($elements, $namespace, $data);

        if (! empty($errors)) {
            return new JsonResponse([
                'errors' => $errors,
            ]);
        }

        DB::transaction(function () use ($elements) {
            foreach ($elements as $element) {
                if (! $this->elements->saveElement($element)) {
                    Log::error("Couldn’t save element {$element->id}: ".implode(', ', $element->getFirstErrors()));
                    abort(500, "Couldn’t save element {$element->id}");
                }
            }
        });

        return $this->asSuccess();
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, array<string, mixed>>  $data
     * @return Collection<int, ElementInterface>
     */
    private function getElements(string $elementType, int $siteId, array $data): Collection
    {
        $elementIds = array_map(
            fn (string $key): int => (int) Str::chopStart($key, 'element-'),
            array_keys($data),
        );

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
     * @return array<int, array<string, array<int, string>>>
     */
    private function validateElements(Collection $elements, string $namespace, array $data): array
    {
        $errors = [];

        foreach ($elements as $element) {
            $attributes = Arr::except($data["element-$element->id"] ?? [], 'fields');

            if ($element instanceof User) {
                $attributes = Arr::except($attributes, User::SENSITIVE_ATTRIBUTES);
            }

            if (! empty($attributes)) {
                $element->ruleset->withScenario(
                    ElementRules::SCENARIO_LIVE,
                    fn () => $element->setAttributesFromRequest($attributes),
                );
            }

            $element->setFieldValuesFromRequest("$namespace.element-$element->id.fields");

            if ($element->getIsUnpublishedDraft()) {
                $element->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
            } elseif ($element->enabled && $element->getEnabledForSite()) {
                $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
            }

            $names = array_merge(
                array_keys($attributes),
                array_map(
                    fn (string $handle): string => "field:$handle",
                    array_keys($data["element-$element->id"]['fields'] ?? []),
                ),
            );

            if (! $element->validate($names)) {
                $errors[$element->getCanonicalId()] = $element->errors()->getMessages();
            }
        }

        return $errors;
    }
}
