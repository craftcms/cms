<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Activity\ActivityComments;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Http\Requests\ActivityMentionSuggestionsRequest;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LogicException;

readonly class ActivityMentionSuggestionsController
{
    private const int MaxCandidates = 250;

    public function __construct(private ActivityComments $comments) {}

    public function __invoke(ActivityMentionSuggestionsRequest $request): JsonResponse
    {
        return new JsonResponse($this->suggestions($request, $request->subject())->all());
    }

    /** @return Collection<int, array{label: string, value: string, keywords: array{string}, data: array{hint: string}}> */
    private function suggestions(
        ActivityMentionSuggestionsRequest $request,
        ElementInterface $subject,
    ): Collection {
        $search = Str::lower(Str::after($request->string('query')->toString(), '@'));
        $limit = $request->integer('limit', 10);
        $query = User::find()->status(User::STATUS_ACTIVE);

        if ($search !== '') {
            $query->where(fn (Builder $query) => $query
                ->whereLike('users.fullName', "%$search%")
                ->orWhereLike('users.username', "%$search%"));
        }

        return $query
            ->orderBy('elements.id')
            ->limit(self::MaxCandidates)
            ->collect()
            ->filter(fn (User $user): bool => $this->comments->canMention($user, $subject))
            ->take($limit)
            ->map(function (User $user): array {
                $username = $user->username ?? throw new LogicException('Mentionable users require a username.');
                $escapedUsername = Str::replace(['\\', '[', ']'], ['\\\\', '\\[', '\\]'], $username);

                return [
                    'label' => $user->name,
                    'value' => sprintf('[@%s](craft-user:%d)', $escapedUsername, $user->id),
                    'keywords' => [$username],
                    'data' => ['hint' => "@$username"],
                ];
            })
            ->values();
    }
}
