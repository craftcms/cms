<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\Data\ElementActivity as ElementActivityData;
use CraftCms\Cms\Element\ElementActivity;
use CraftCms\Cms\Element\Enums\ElementActivityType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\ElementCrumbs;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class ElementActivityController
{
    use ElementCrumbs;

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

        $user = $this->request->craftUser();
        if (! $user) {
            abort(401);
        }

        $activity = $this->elementActivity->getRecentActivity($element, $user->getCraftUserId());

        $this->elementActivity->trackActivity($element, ElementActivityType::View, $user->asElement());

        return new JsonResponse([
            'activity' => $activity->map(fn (ElementActivityData $record) => $record->toActivityRow($element))->all(),
            'updatedTimestamp' => $element->dateUpdated->getTimestamp(),
            'canonicalUpdatedTimestamp' => $element->getCanonical()->dateUpdated->getTimestamp(),
        ]);
    }

    public function index(): CpScreenResponse|Response
    {
        $element = $this->request->element([
            'id' => $this->request->route('id'),
        ]);

        if ($element instanceof Response) {
            return $element;
        }

        if (! $element instanceof Entry) {
            abort(400, 'No entry was identified by the request.');
        }

        $entry = $element->getCanonical(true);

        return new CpScreenResponse()
            ->title(t('Activity for “{title}”', [
                'title' => $entry->getUiLabel(),
            ]))
            ->crumbs([
                ...$this->crumbs($entry, current: false),
                [
                    'label' => t('Activity'),
                    'current' => true,
                ],
            ])
            ->inertiaPage('content/Activity', [
                'activityTimelineUrl' => Url::actionUrl('elements/activity', ['all' => true]),
                'elementType' => $entry::class,
                'elementId' => $entry->id,
                'siteId' => $entry->siteId,
            ]);
    }
}
