<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Activity\Activities;
use CraftCms\Cms\Activity\ActivityComments;
use CraftCms\Cms\Activity\ActivityTimelinePresenter;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\EventTypes\CommentCreated;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Http\Requests\ActivityCommentRequest;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Http\JsonResponse;

use function CraftCms\Cms\currentUserElement;

readonly class ActivityCommentsController
{
    public function __construct(
        private Activities $activities,
        private ActivityComments $comments,
        private ActivityTimelinePresenter $presenter,
    ) {}

    public function store(ActivityCommentRequest $request): JsonResponse
    {
        $subject = $request->subject();
        $element = $request->subjectElement();
        $user = $this->currentUser();
        $event = $this->comments->create(
            $subject,
            $user,
            $this->site($element),
            $request->string('markdown')->toString(),
        );

        return new JsonResponse([
            'event' => $this->presenter->events(collect([$event]), $user)->first(),
        ]);
    }

    public function update(ActivityCommentRequest $request): JsonResponse
    {
        $subject = $request->subject();
        $element = $request->subjectElement();
        $comment = $this->comment($subject, $element, $request->string('commentId')->toString());
        $user = $this->currentUser();

        abort_unless($comment->actorId === $user->id, 403, 'Only the comment author may edit it.');

        $version = $this->comments->edit(
            $comment,
            $user,
            $request->string('markdown')->toString(),
            $subject,
        );

        return $this->versionResponse($comment, $version, $user);
    }

    public function destroy(ActivityCommentRequest $request): JsonResponse
    {
        $subject = $request->subject();
        $element = $request->subjectElement();
        $comment = $this->comment($subject, $element, $request->string('commentId')->toString());
        $user = $this->currentUser();

        abort_unless(
            $comment->actorId === $user->id || $user->admin,
            403,
            'Only the comment author or an administrator may remove it.',
        );

        return $this->versionResponse(
            $comment,
            $this->comments->delete($comment, $user),
            $user,
        );
    }

    private function comment(
        ElementInterface $subject,
        ElementInterface $element,
        string $commentId,
    ): ActivityEvent {
        return $this->activities->query()
            ->subject(ActivitySubject::fromElement($subject))
            ->where('siteId', $this->site($element)?->id)
            ->where('eventType', CommentCreated::class)
            ->whereNull('rootEventId')
            ->findOrFail($commentId);
    }

    private function versionResponse(
        ActivityEvent $comment,
        ActivityEvent $version,
        User $viewer,
    ): JsonResponse {
        return new JsonResponse([
            'event' => $this->presenter->events(
                collect([$comment]),
                $viewer,
                collect([$comment->id => $version]),
            )->first(),
        ]);
    }

    private function site(ElementInterface $element): ?Site
    {
        if ($element->siteId === null) {
            return null;
        }

        return Site::get($element->siteId) ?? abort(400, 'The activity site could not be found.');
    }

    private function currentUser(): User
    {
        return currentUserElement() ?? abort(401);
    }
}
