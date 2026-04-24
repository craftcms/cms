<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\Data\ElementActivity as ElementActivityData;
use CraftCms\Cms\Element\ElementActivity;
use CraftCms\Cms\Element\Enums\ElementActivityType;
use CraftCms\Cms\Http\Requests\ElementRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

readonly class ElementActivityController
{
    public function __construct(
        private ElementRequest $request,
        private ElementActivity $elementActivity,
    ) {}

    public function __invoke(): Response
    {
        $element = $this->request->element();

        if ($element instanceof Response) {
            return $element;
        }

        if (! $element || $element->getIsRevision()) {
            abort(400, 'No element was identified by the request.');
        }

        $activity = $this->elementActivity->getRecentActivity($element, $this->request->user()->id);

        $this->elementActivity->trackActivity($element, ElementActivityType::View, $this->request->user());

        return new JsonResponse([
            'activity' => $activity->map(fn (ElementActivityData $record) => $record->toActivityRow($element))->all(),
            'updatedTimestamp' => $element->dateUpdated->getTimestamp(),
            'canonicalUpdatedTimestamp' => $element->getCanonical()->dateUpdated->getTimestamp(),
        ]);
    }
}
