<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cp\Enums\ChipThumbSize;
use CraftCms\Cms\Cp\RequestedSite;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementIndexes;
use CraftCms\Cms\Element\ElementIndexState;
use CraftCms\Cms\Element\ElementSources as ElementSourcesService;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Ui\Controls\ElementSelect;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Which of the elements on the CP's clipboard a relation field could relate,
 * so the field only offers to paste what its element selector would let
 * someone choose.
 *
 * An element qualifies when one of the field's sources lists it, with the
 * field's criteria and selection condition applied the way the selector
 * applies them. Qualifying elements come back in the shape the field draws
 * from, in the order they were copied.
 *
 * @since 6.0.0
 */
readonly class PasteableElementsController
{
    /** How many copied elements are checked at once. */
    private const int LIMIT = 100;

    public function __invoke(
        ElementIndexRequest $request,
        ElementIndexes $elementIndexes,
        ElementIndexState $elementIndexState,
        RequestedSite $requestedSite,
    ): JsonResponse {
        $request->validate([
            'elementIds' => ['required', 'array', 'max:'.self::LIMIT],
            'elementIds.*' => ['integer'],
            'sources' => ['nullable', 'array'],
            'sources.*' => ['string'],
            'showFolders' => ['nullable', 'boolean'],
            'siteId' => ['nullable', 'integer'],
            'viewMode' => ['nullable', 'string', Rule::in(ElementSelect::viewModes())],
            'thumbSize' => ['nullable', Rule::enum(ChipThumbSize::class)],
        ]);

        $elementType = $request->elementType();
        $elementIds = array_values(array_unique(array_map(intval(...), $request->input('elementIds'))));
        $allowedSources = $request->input('sources');
        // The site the field is being edited in, when it's one the user can edit.
        $siteId = Sites::getEditableSiteIds()->contains($request->integer('siteId'))
            ? $request->integer('siteId')
            : ($requestedSite->get()->id ?? Sites::getPrimarySite()->id);

        // Resolved as the selector modal resolves them, so a nested source the field allows, such as an Assets
        // field's restricted subfolder, counts too.
        $sources = $elementIndexState
            ->sources($elementType, $request->context(), restrictTo: is_array($allowedSources) ? array_values($allowedSources) : null)
            ->filter(fn (array $source): bool => $source['type'] !== ElementSourcesService::TYPE_HEADING && isset($source['key']));
        // The selector only opens subfolders when it lists them.
        $includeSubfolders = ! $request->has('showFolders') || $request->boolean('showFolders');

        $condition = $request->condition();
        $criteria = $request->criteria();
        $matchedIds = [];

        foreach ($sources as $source) {
            $remainingIds = array_values(array_diff($elementIds, $matchedIds));

            if ($remainingIds === []) {
                break;
            }

            // The selector can open a folder's subfolders, so anything in them can be chosen.
            if ($elementType === Asset::class && $includeSubfolders && isset($source['criteria']['folderId'])) {
                $source['criteria']['includeSubfolders'] = true;
            }

            $query = $elementIndexes->buildQueryState(
                elementType: $elementType,
                source: $source,
                condition: $condition,
                criteria: $criteria,
            )['query'];

            array_push($matchedIds, ...$query
                ->siteId($siteId)
                ->status($source['criteria']['status'] ?? null)
                ->id($remainingIds)
                ->ids());
        }

        $matchedIds = array_values(array_intersect($elementIds, array_map(intval(...), $matchedIds)));

        if ($matchedIds === []) {
            return new JsonResponse(['elements' => []]);
        }

        $viewMode = $request->input('viewMode', ElementSelect::VIEW_MODE_LIST);
        $thumbSize = $request->enum('thumbSize', ChipThumbSize::class);
        $elements = $elementType::find()
            ->siteId($siteId)
            ->status(null)
            ->id($matchedIds)
            ->fixedOrder()
            ->all();

        return new JsonResponse([
            'elements' => array_values(array_map(
                fn (ElementInterface $element): array => ElementSelect::presentation($element, $viewMode, $thumbSize),
                array_filter($elements, fn (ElementInterface $element): bool => Gate::check('view', $element)),
            )),
        ]);
    }
}
