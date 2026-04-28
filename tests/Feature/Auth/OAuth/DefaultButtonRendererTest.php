<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\OAuth\Actions\ButtonRenderer;
use CraftCms\Cms\Auth\OAuth\Data\ButtonData;
use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;

test('it returns safe encoded markup for the login button', function () {
    $renderer = new ButtonRenderer;

    $button = $renderer->handle(new ButtonData(
        provider: new ProviderDefinition(
            handle: 'test',
            driver: 'test',
            providerClass: null,
            name: 'Test',
            label: 'Continue with <Test OAuth>',
            clientId: null,
            clientSecret: null,
        ),
        isCpRequest: true,
        url: '/admin/oauth/test/redirect',
        label: 'Continue with <Test OAuth>',
    ));

    expect($button->toHtml())
        ->toContain('&lt;Test OAuth&gt;')
        ->toContain('class="btn"')
        ->toContain('data-provider="test"')
        ->toContain('/admin/oauth/test/redirect');
});
