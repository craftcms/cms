<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\RequestedSite;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
});

it('returns a requested site by handle when editable', function () {
    $sites = app(Sites::class);
    $defaultSite = $sites->getCurrentSite();

    request()->query->set('site', $defaultSite->handle);

    $requestedSite = app(RequestedSite::class)->get();

    expect($requestedSite)->not->toBeNull()
        ->and($requestedSite?->id)->toBe($defaultSite->id);
});

it('returns a site after reset', function () {
    $service = app(RequestedSite::class);

    expect($service->get())->not->toBeNull();

    $service->reset();

    expect($service->get())->not->toBeNull();
});
