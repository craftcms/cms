<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Ai\Assistant\AssistantAgent;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Deletes AI assistant conversations that haven’t been continued for 30 days, and those of users that no longer exist.
 *
 * @since 6.0.0
 */
class DeleteStaleAiConversations extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        $this->components->task(
            'deleting stale AI conversations',
            fn () => DB::table(Table::AGENT_CONVERSATIONS)
                ->tap(AssistantAgent::onlyItsConversations(...))
                ->where(fn (Builder $query) => $query
                    // laravel/ai writes conversations in the system time zone
                    ->where('updated_at', '<', now()->subDays(30)->toDateTimeString())
                    ->orWhere(fn (Builder $query) => $query
                        ->where('participant_type', User::class)
                        ->whereNotExists(fn (Builder $query) => $query
                            ->from(Table::USERS)
                            ->whereColumn(Table::USERS.'.id', Table::AGENT_CONVERSATIONS.'.participant_id'))))
                ->orderBy('id')
                ->chunkById($this->garbageCollection::CHUNK_SIZE, function (Collection $conversations): void {
                    $ids = $conversations->pluck('id');

                    DB::table(Table::AGENT_CONVERSATION_MESSAGES)->whereIn('conversation_id', $ids)->delete();
                    DB::table(Table::AGENT_CONVERSATIONS)->whereIn('id', $ids)->delete();
                }),
        );
    }
}
