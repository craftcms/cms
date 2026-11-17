<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Query;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;

/**
 * Deletes expired and revoked OAuth credentials, and registered clients that can no longer be used.
 *
 * @since 6.0.0
 */
class DeleteStaleOAuthData extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        $this->components->task('deleting stale OAuth data', function (): void {
            $now = Query::prepareDateForDb(now());
            $stale = fn (Builder $query) => $query->where('revoked', true)->orWhere('expires_at', '<', $now);

            DB::table(Table::OAUTH_AUTH_CODES)->where($stale)->delete();
            DB::table(Table::OAUTH_REFRESH_TOKENS)->where($stale)->delete();

            // Keep access tokens with a live refresh token, which would be deleted along with them
            DB::table(Table::OAUTH_ACCESS_TOKENS)
                ->where($stale)
                ->whereNotIn('id', DB::table(Table::OAUTH_REFRESH_TOKENS)->select('access_token_id'))
                ->delete();

            // Clients without a provider that have nothing live left, and were either revoked, or registered through an
            // OAuth resource over a day ago but never authorized (their `updated_at` is null until they are).
            // Registration is anonymous, so these would otherwise pile up.
            DB::table(Table::OAUTH_CLIENTS)
                ->whereNull('provider')
                ->whereNotIn('id', DB::table(Table::OAUTH_ACCESS_TOKENS)->select('client_id'))
                ->whereNotIn('id', DB::table(Table::OAUTH_AUTH_CODES)->select('client_id'))
                ->where(fn (Builder $query) => $query
                    ->where('revoked', true)
                    ->orWhere(fn (Builder $query) => $query
                        ->whereNull('updated_at')
                        ->where('created_at', '<', Passport::client()->fromDateTime(now()->subDay()))))
                ->delete();
        });
    }
}
