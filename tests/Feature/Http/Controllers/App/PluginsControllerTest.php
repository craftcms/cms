<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\App\PluginsController;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    loadTestPlugin();
});

test('get plugin license info validates and sorts plugin results', function () {
    $api = Mockery::mock(Api::class);
    $api->shouldReceive('getLicenseInfo')
        ->once()
        ->with(['plugins'])
        ->andReturn([
            'pluginLicenses' => [
                [
                    'key' => 'ABC123ABC123ABC123ABC123',
                    'edition' => 'pro',
                    'expired' => false,
                    'plugin' => [
                        'handle' => 'zebra-plugin',
                        'name' => 'Zebra Plugin',
                        'shortDescription' => 'Zebra description',
                        'packageName' => 'vendor/zebra-plugin',
                        'latestVersion' => '2.0.0',
                    ],
                ],
                [
                    'key' => 'XYZ789XYZ789XYZ789XYZ789',
                    'edition' => 'pro',
                    'expired' => true,
                    'renewalUrl' => 'https://example.com/renew',
                    'renewalPrice' => 49,
                    'renewalCurrency' => 'USD',
                    'plugin' => [
                        'handle' => 'alpha-plugin',
                        'name' => 'Alpha Plugin',
                        'shortDescription' => 'Alpha description',
                        'packageName' => 'vendor/alpha-plugin',
                        'latestVersion' => '1.0.0',
                    ],
                ],
            ],
        ]);
    app()->instance(Api::class, $api);

    postJson(action([PluginsController::class, 'getLicenseInfo']))
        ->assertOk()
        ->assertJsonPath('alpha-plugin.name', 'Alpha Plugin')
        ->assertJsonPath('alpha-plugin.expired', true)
        ->assertJsonPath('alpha-plugin.renewalUrl', 'https://example.com/renew')
        ->assertJsonPath('zebra-plugin.name', 'Zebra Plugin')
        ->assertJsonPath('zebra-plugin.licenseKeyStatus', LicenseKeyStatus::Valid->value);
});

test('update plugin license validates required handle', function () {
    postJson(action([PluginsController::class, 'updateLicense']))
        ->assertJsonValidationErrors(['handle']);
});

test('update plugin license stores the normalized key', function () {
    $response = postJson(action([PluginsController::class, 'updateLicense']), [
        'handle' => 'test-plugin',
        'key' => 'abc-123-abc-123-abc-123-abc-123',
    ]);

    $response->assertOk();

    expect($response->json())->toBe(1);

    expect(app(Plugins::class)->getPluginLicenseKey('test-plugin'))
        ->toBe('ABC123ABC123ABC123ABC123');
});
