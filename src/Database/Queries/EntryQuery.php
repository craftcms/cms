<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries;

use Closure;
use craft\elements\Entry;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Date;

final class EntryQuery extends ElementQuery
{
    public ?bool $withStructure = true;

    public function __construct(array $config = [])
    {
        // Default status
        if (! isset($config['status'])) {
            $config['status'] = [
                Entry::STATUS_LIVE,
            ];
        }

        parent::__construct(Entry::class, $config);

        $this->joinElementTable(Table::ENTRIES);

        $this->query->addSelect([
            'entries.sectionId as sectionId',
            'entries.fieldId as fieldId',
            'entries.primaryOwnerId as primaryOwnerId',
            'entries.typeId as typeId',
            'entries.postDate as postDate',
            'entries.expiryDate as expiryDate',
        ]);

        if (Cms::config()->staticStatuses) {
            $this->query->addSelect(['entries.status as status']);
        }
    }

    protected function statusCondition(string $status): Closure
    {
        if (
            Cms::config()->staticStatuses &&
            in_array($status, [Entry::STATUS_LIVE, Entry::STATUS_PENDING, Entry::STATUS_EXPIRED])
        ) {
            return fn (Builder $query) => $query->where('elements.enabled', true)->where('elements_sites.enabled', true)->where('entries.status', $status);
        }

        // Always consider “now” to be the current time @ 59 seconds into the minute.
        // This makes entry queries more cacheable, since they only change once every minute (https://github.com/craftcms/cms/issues/5389),
        // while not excluding any entries that may have just been published in the past minute (https://github.com/craftcms/cms/issues/7853).
        $currentTime = Date::now()->endOfMinute();

        return match ($status) {
            Entry::STATUS_LIVE => fn (Builder $query) => $query
                ->where('elements.enabled', true)
                ->where('elements_sites.enabled', true)
                ->where('entries.postDate', '<=', $currentTime)
                ->where(function (Builder $query) use ($currentTime) {
                    $query->whereNull('entries.expiryDate')
                        ->orWhere('entries.expiryDate', '>', $currentTime);
                }),
            Entry::STATUS_PENDING => fn (Builder $query) => $query
                ->where('elements.enabled', true)
                ->where('elements_sites.enabled', true)
                ->where('entries.postDate', '>', $currentTime),
            Entry::STATUS_EXPIRED => fn (Builder $query) => $query
                ->where('elements.enabled', true)
                ->where('elements_sites.enabled', true)
                ->whereNotNull('entries.expiryDate')
                ->where('entries.expiryDate', '<=', $currentTime),
            default => parent::statusCondition($status),
        };
    }
}
