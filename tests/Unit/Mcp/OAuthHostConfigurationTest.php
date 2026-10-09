<?php

declare(strict_types=1);

use Laravel\Passport\Contracts\AuthorizationViewResponse;
use Laravel\Passport\Passport;
use Symfony\Component\HttpFoundation\Response;

trait ConfiguresHostPassport
{
    protected function resolveApplicationConfiguration($app): void
    {
        parent::resolveApplicationConfiguration($app);

        $previousScopes = Passport::$scopes;
        $previousDefaultScopes = Passport::defaultScopes();
        $this->beforeApplicationDestroyed(static function () use ($previousScopes, $previousDefaultScopes): void {
            Passport::tokensCan($previousScopes);
            Passport::defaultScopes($previousDefaultScopes);
        });

        $app->make('config')->set('passport.guard', 'host-guard');
        Passport::tokensCan(['host:read' => 'Read host data']);
        Passport::defaultScopes(['host:read']);
        Passport::authorizationView(static fn (): Response => new Response('Host authorization'));
    }
}

uses(ConfiguresHostPassport::class);

it('preserves host Passport configuration during provider registration and boot', function (): void {
    expect(config('passport.guard'))->toBe('host-guard')
        ->and(Passport::defaultScopes())->toBe(['host:read'])
        ->and(Passport::$scopes['host:read'] ?? null)->toBe('Read host data')
        ->and(Passport::hasScope('mcp:use'))->toBeTrue();

    $response = app(AuthorizationViewResponse::class)->withParameters([])->toResponse(request());

    expect($response->getContent())->toBe('Host authorization');
});
