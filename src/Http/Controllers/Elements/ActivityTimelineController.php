<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Activity\Activities;
use CraftCms\Cms\Activity\ActivityTimelinePresenter;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Http\Requests\ActivityTimelineRequest;
use Illuminate\Http\JsonResponse;

use function CraftCms\Cms\currentUserElement;

readonly class ActivityTimelineController
{
    private const int EmbeddedLimit = 25;

    public function __construct(
        private Activities $activities,
        private ActivityTimelinePresenter $presenter,
    ) {}

    public function __invoke(ActivityTimelineRequest $request): JsonResponse
    {
        $subject = $request->subject();
        $element = $request->subjectElement();
        $query = $this->activities->query()
            ->subject(ActivitySubject::fromElement($subject))
            ->whereNull('rootEventId');

        if ($element->siteId !== null) {
            $query->site($element->siteId);
        }

        if (! $request->boolean('all')) {
            $query->limit(self::EmbeddedLimit);
        }

        return new JsonResponse([
            'events' => $this->presenter->events(
                $query->get()->reverse()->values(),
                currentUserElement() ?? abort(401),
            )->all(),
        ]);
    }
}
