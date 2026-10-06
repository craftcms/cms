<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\McpConfig;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Mcp\Events\CollectingAdminInstructions;
use CraftCms\Cms\Mcp\PublicRouteRegistrar;
use CraftCms\Cms\Mcp\Settings;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;

it('delivers core instructions with site and plugin additions through admin discovery only', function (Closure $config): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:use'], 'craft-mcp');
    Cms::config()->mcp($config());

    Event::listen(CollectingAdminInstructions::class, function (CollectingAdminInstructions $event): void {
        $event->instructions[] = 'Use reviews.list before changing a product.';
        $event->instructions[] = '  ';
    });
    Event::listen(CollectingAdminInstructions::class, function (CollectingAdminInstructions $event): void {
        $event->instructions[] = 'Use reviews.submit to request approval.';
    });

    $instructions = McpRequest::send($this, 'server/discover')
        ->assertOk()
        ->json('result.instructions');

    expect($instructions)->toContain('hardDelete', 'elements.schema')
        ->toEndWith("Preserve legal notices.\n\nUse reviews.list before changing a product.\n\nUse reviews.submit to request approval.");

    $route = '/_test/public-instructions';
    app()->instance(Settings::class, new Settings(['publicEnabled' => true, 'publicRoute' => $route]));
    app(PublicRouteRegistrar::class)->register();

    $publicInstructions = $this->postJson($route, McpRequest::payload('server/discover'), McpRequest::headers('server/discover'))
        ->assertOk()
        ->json('result.instructions');

    expect($publicInstructions)->toContain('craft-context-get')
        ->not->toContain('Preserve legal notices.', 'reviews.list', 'reviews.submit');
})->with([
    'array config' => [fn (): array => ['instructions' => 'Preserve legal notices.']],
    'JSON config' => [fn (): string => '{"instructions":"Preserve legal notices."}'],
    'fluent config' => [fn (): McpConfig => McpConfig::create()->instructions('Preserve legal notices.')],
]);

it('includes registered plugin element references in default admin instructions', function (): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:use'], 'craft-mcp');

    $type = new class extends Entry
    {
        public static function refHandle(): string
        {
            return 'product';
        }
    };
    app(ElementTypes::class)->register($type::class);

    $instructions = McpRequest::send($this, 'server/discover')->assertOk()->json('result.instructions');

    expect($instructions)->toContain('hardDelete', 'product: `'.$type::class.'`');
});
