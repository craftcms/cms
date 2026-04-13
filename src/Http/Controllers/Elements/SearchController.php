<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use Closure;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Elements as ElementElements;
use CraftCms\Cms\Element\Exceptions\InvalidTypeException;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Search;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use function CraftCms\Cms\t;

readonly class SearchController
{
    public function __construct(
        private Request $request,
        private Conditions $conditions,
        private ElementElements $elements,
    ) {}

    public function __invoke()
    {
        $this->request->validate([
            'elementType' => ['required', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                if (! ComponentHelper::validateComponentClass($value, ElementInterface::class)) {
                    $fail(new InvalidTypeException((string) $value, ElementInterface::class)->getMessage());
                }
            }],
            'siteId' => ['nullable'],
            'criteria' => ['nullable', 'array'],
            'condition' => ['nullable', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_array($value) && ! is_string($value)) {
                    $fail(t('The {attribute} field must be a string or array.', ['attribute' => $attribute]));

                    return;
                }

                if (is_array($value)) {
                    $class = $value['class'] ?? null;

                    if (! is_string($class) || trim($class) === '') {
                        $fail(t('The {attribute} field must contain a `class` value.', ['attribute' => $attribute]));
                    }
                }
            }],
            'excludeIds' => ['nullable', 'array'],
            'excludeIds.*' => ['integer'],
            'referenceElementId' => ['nullable', 'integer'],
            'referenceElementOwnerId' => ['nullable', 'integer'],
            'referenceElementSiteId' => ['nullable', 'integer'],
            'search' => ['required', 'string', 'max:255'],
        ]);

        /** @var class-string<ElementInterface> $elementType */
        $elementType = $this->request->input('elementType');

        $query = $elementType::find()
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
        if (! $this->request->has('condition')) {
            return;
        }

        $condition = $this->conditions->createCondition($this->request->input('condition'));

        if (! $condition instanceof ElementCondition) {
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
