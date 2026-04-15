<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Support\Search;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Http\JsonResponse;

readonly class SearchController
{
    public function __construct(
        private Elements $elements,
        private ElementRequest $request,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->request->validate([
            'siteId' => ['nullable'],
            'criteria' => ['nullable', 'array'],
            'excludeIds' => ['nullable', 'array'],
            'excludeIds.*' => ['integer'],
            'referenceElementId' => ['nullable', 'integer'],
            'referenceElementOwnerId' => ['nullable', 'integer'],
            'referenceElementSiteId' => ['nullable', 'integer'],
            'search' => ['required', 'string', 'max:255'],
        ]);

        $query = $this->request->elementType()::find()
            ->siteId($this->request->input('siteId'))
            ->search($this->request->input('search'))
            ->orderByDesc('score')
            ->limit(5);

        if ($criteria = $this->request->array('criteria')) {
            // Remove unsupported criteria attributes
            $criteria = ElementHelper::cleanseQueryCriteria($criteria);

            Typecast::configure($query, $criteria);
        }

        $this->applyCondition($query);

        $elements = $query->get();

        if ($elements->isEmpty()) {
            return new JsonResponse([
                'elements' => [],
                'exactMatch' => false,
            ]);
        }

        $return = [];
        $exactMatches = [];
        $excludes = [];
        $exactMatch = false;

        $search = Search::normalizeKeywords($this->request->input('search', ''));

        foreach ($elements as $element) {
            $exclude = in_array($element->id, $this->request->array('excludeIds'));

            $return[] = [
                'id' => $element->id,
                'title' => $element->title,
                'html' => app(ElementHtml::class)->chipHtml($element, [
                    'hyperlink' => false,
                    'class' => 'chromeless',
                ]),
                'exclude' => $exclude,
            ];

            $title = $element->title ?? (string) $element;
            $title = Search::normalizeKeywords($title);

            if ($title === $search) {
                $exactMatches[] = 1;
                $exactMatch = true;
            } else {
                $exactMatches[] = 0;
            }

            $excludes[] = $exclude ? 1 : 0;
        }

        // prevent the default sort order from changing beyond $excludes + $exactMatches
        $range = range(1, count($return));

        array_multisort($excludes, SORT_ASC, $exactMatches, SORT_DESC, $range, $return);

        return new JsonResponse([
            'elements' => $return,
            'exactMatch' => $exactMatch,
        ]);
    }

    private function applyCondition(ElementQueryInterface $query): void
    {
        if (! $condition = $this->request->condition()) {
            return;
        }

        if ($referenceElementId = $this->request->input('referenceElementId')) {
            $ownerId = $this->request->input('referenceElementOwnerId');
            $siteId = $this->request->input('referenceElementSiteId');
            $criteria = [];

            if ($ownerId) {
                $criteria['ownerId'] = $ownerId;
            }

            $condition->referenceElement = $this->elements->getElementById(
                (int) $referenceElementId,
                siteId: $siteId,
                criteria: $criteria,
            );
        }

        $condition->modifyQuery($query);
    }
}
