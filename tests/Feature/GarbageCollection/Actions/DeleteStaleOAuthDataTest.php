<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\GarbageCollection\Actions\DeleteStaleOAuthData;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;

it('deletes stale OAuth credentials and registered clients that can no longer be used', function () {
    $userId = User::query()->firstOrFail()->id;
    $clients = app(ClientRepository::class);
    $past = Query::prepareDateForDb(now()->subHour());
    $future = Query::prepareDateForDb(now()->addHour());

    $client = function (string $name, bool $revoked = false, bool $authorized = true, string $age = '2 days') use ($clients): string {
        $id = (string) $clients->createAuthorizationCodeGrantClient($name, ['https://client.example/callback'], false)->getKey();
        DB::table(Table::OAUTH_CLIENTS)->where('id', $id)->update([
            'revoked' => $revoked,
            'created_at' => now()->sub($age)->toDateTimeString(),
            'updated_at' => $authorized ? now()->toDateTimeString() : null,
        ]);

        return $id;
    };
    $accessToken = fn (string $id, string $clientId, string $expiresAt, bool $revoked = false) => DB::table(Table::OAUTH_ACCESS_TOKENS)->insert([
        'id' => $id, 'user_id' => $userId, 'client_id' => $clientId, 'scopes' => '[]', 'revoked' => $revoked, 'expires_at' => $expiresAt,
    ]);
    $refreshToken = fn (string $id, string $accessTokenId, string $expiresAt) => DB::table(Table::OAUTH_REFRESH_TOKENS)->insert([
        'id' => $id, 'access_token_id' => $accessTokenId, 'revoked' => false, 'expires_at' => $expiresAt,
    ]);
    $authCode = fn (string $id, string $clientId, string $expiresAt) => DB::table(Table::OAUTH_AUTH_CODES)->insert([
        'id' => $id, 'user_id' => $userId, 'client_id' => $clientId, 'scopes' => '[]', 'revoked' => false, 'expires_at' => $expiresAt,
    ]);

    $authorized = $client('Authorized');
    $accessToken('live', $authorized, $future);
    $accessToken('refreshable', $authorized, $past);
    $refreshToken('live-refresh', 'refreshable', $future);
    $accessToken('expired', $authorized, $past);
    $refreshToken('expired-refresh', 'expired', $past);
    $accessToken('revoked', $authorized, $future, revoked: true);
    $authCode('expired-code', $authorized, $past);
    $client('Never authorized', authorized: false);
    $client('Recently registered', authorized: false, age: '1 hour');
    $pendingCode = $client('Never authorized, with a pending code', authorized: false);
    $authCode('live-code', $pendingCode, $future);
    $client('Revoked', revoked: true);
    $revokedWithToken = $client('Revoked, with a live token', revoked: true);
    $accessToken('revoked-client-token', $revokedWithToken, $future);
    $client('Authorized, without tokens');
    $personal = (string) $clients->createPersonalAccessGrantClient('Personal', 'craft')->getKey();
    DB::table(Table::OAUTH_CLIENTS)->where('id', $personal)->update(['revoked' => true]);

    app(DeleteStaleOAuthData::class)();

    // PostgreSQL pads the `char(80)` IDs with spaces
    $ids = fn (string $table) => DB::table($table)->orderBy('id')->pluck('id')->map(fn (string $id) => rtrim($id))->all();

    expect($ids(Table::OAUTH_ACCESS_TOKENS))->toBe(['live', 'refreshable', 'revoked-client-token'])
        ->and($ids(Table::OAUTH_REFRESH_TOKENS))->toBe(['live-refresh'])
        ->and($ids(Table::OAUTH_AUTH_CODES))->toBe(['live-code'])
        ->and(DB::table(Table::OAUTH_CLIENTS)->orderBy('name')->pluck('name')->all())->toBe([
            'Authorized',
            'Authorized, without tokens',
            'Never authorized, with a pending code',
            'Personal',
            'Recently registered',
            'Revoked, with a live token',
        ]);
});
