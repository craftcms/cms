<?php

declare(strict_types=1);

use CraftCms\Cms\Edition\Exceptions\WrongEditionException;
use Illuminate\Http\Request;

it('renders as a 404 response outside debug mode', function () {
    config()->set('app.debug', false);

    $response = new WrongEditionException('Craft Pro is required for this.')
        ->render(Request::create('/wrong-edition'));

    expect($response->getStatusCode())->toBe(404);
});

it('defers to the default exception renderer in debug mode', function () {
    config()->set('app.debug', true);

    $response = new WrongEditionException('Craft Pro is required for this.')
        ->render(Request::create('/wrong-edition'));

    expect($response)->toBeFalse();
});
