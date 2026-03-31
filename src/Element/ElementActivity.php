<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use craft\base\ElementInterface;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Data\ElementActivity as ElementActivityData;
use CraftCms\Cms\Element\Enums\ElementActivityType;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

#[Singleton]
readonly class ElementActivity
{
    /**
     * @return Collection<int, ElementActivityData>
     */
    public function getRecentActivity(ElementInterface $element, ?int $excludeUserId = null): Collection
    {
        $results = DB::table(Table::ELEMENTACTIVITY)
            ->select(['userId', 'siteId', 'draftId', 'type', 'timestamp'])
            ->where('elementId', $element->getCanonicalId())
            ->where('timestamp', '>', now()->subMinute())
            ->latest('timestamp')
            ->when(
                $excludeUserId,
                fn (Builder $query) => $query->whereNot('userId', $excludeUserId),
            )
            ->get();

        if ($results->isEmpty()) {
            return collect();
        }

        $users = User::find()
            ->id($results->pluck('userId')->unique()->all())
            ->status(null)
            ->get()
            ->keyBy('id')
            ->all();

        /** @var Collection<int, ElementActivityData> $activity */
        $activity = collect();
        /** @var array<int, ElementActivityData> $activityByUserId */
        $activityByUserId = [];
        /** @var array<int, array<int, ElementInterface>> $elements */
        $elements = [];
        $isCanonical = $element->getIsCanonical() || $element->isProvisionalDraft;
        $elements[$isCanonical ? 0 : $element->draftId][$element->siteId] = $element;

        foreach ($results as $result) {
            if (isset($activityByUserId[$result->userId])) {
                $newerRecord = $activityByUserId[$result->userId];

                if ($newerRecord->type === ElementActivityType::View) {
                    if ($result->type !== ElementActivityType::View->value) {
                        $activity = $activity->filter(fn (ElementActivityData $record) => $record !== $newerRecord);
                        unset($activityByUserId[$result->userId]);
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }
            }

            $elementKey = $result->draftId ?: 0;

            if (! isset($elements[$elementKey][$result->siteId])) {
                $resultElement = $element::find()
                    ->id($result->draftId ? null : $element->getCanonicalId())
                    ->draftId($result->draftId)
                    ->site('*')
                    ->preferSites([$result->siteId, $element->siteId])
                    ->status(null)
                    ->one();

                if (! $resultElement) {
                    Log::warning(sprintf(
                        'Couldn’t load %s element %s%s for site %s',
                        $element::class,
                        $element->getCanonicalId(),
                        $result->draftId ? " (draft {$result->draftId})" : '',
                        $result->siteId,
                    ), [__METHOD__]);

                    continue;
                }

                $elements[$elementKey][$result->siteId] = $resultElement;
            }

            if (! isset($users[$result->userId])) {
                continue;
            }

            $record = new ElementActivityData([
                'user' => $users[$result->userId],
                'element' => $elements[$elementKey][$result->siteId],
                'type' => ElementActivityType::from($result->type),
                'timestamp' => DateTimeHelper::toDateTime($result->timestamp),
            ]);

            $activityByUserId[$result->userId] = $record;
            $activity->push($record);
        }

        return $activity->values();
    }

    public function trackActivity(ElementInterface $element, ElementActivityType $type, ?User $user = null): void
    {
        $user ??= Auth::user();

        if (! $user) {
            throw new InvalidArgumentException('$user must be set if no user is signed in.');
        }

        if ($type === ElementActivityType::Save && $element->isProvisionalDraft) {
            $type = ElementActivityType::Edit;
        }

        $isCanonical = $element->getIsCanonical() || $element->isProvisionalDraft;

        DB::table(Table::ELEMENTACTIVITY)->upsert([
            'elementId' => $element->getCanonicalId(),
            'userId' => $user->id,
            'siteId' => $element->siteId,
            'draftId' => $isCanonical ? null : $element->draftId,
            'type' => $type->value,
            'timestamp' => now(),
        ], ['elementId', 'userId', 'type']);
    }
}
