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
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;

it('keeps startup instructions brief and delivers full guidance through the admin info tool only', function (Closure $config): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:use'], 'craft-mcp');
    $siteInstructions = 'Preserve legal notices.'.str_repeat(' Keep required attribution.', 100);
    Cms::config()->mcp($config($siteInstructions));

    Event::listen(CollectingAdminInstructions::class, function (CollectingAdminInstructions $event): void {
        $event->instructions[] = 'Use reviews.list before changing a product.';
        $event->instructions[] = '  ';
    });
    Event::listen(CollectingAdminInstructions::class, function (CollectingAdminInstructions $event): void {
        $event->instructions[] = 'Use reviews.submit to request approval.';
    });

    $brief = McpRequest::send($this, 'server/discover')->assertOk()->json('result.instructions');
    expect(strlen($brief))->toBeLessThanOrEqual(2048)
        ->and($brief)->toContain('info.get')
        ->not->toContain('Preserve legal notices.', 'reviews.list', 'reviews.submit');

    $instructions = McpRequest::send($this, 'tools/call', ['name' => 'info.get'])
        ->assertOk()->assertJsonPath('result.isError', false)
        ->json('result.structuredContent.instructions');

    expect($instructions)->toContain('hardDelete', 'elements.schema')
        ->toEndWith("$siteInstructions\n\nUse reviews.list before changing a product.\n\nUse reviews.submit to request approval.");

    $route = '/_test/public-instructions';
    app()->instance(Settings::class, new Settings(['publicEnabled' => true, 'publicRoute' => $route]));
    app(PublicRouteRegistrar::class)->register();

    $publicInstructions = $this->postJson($route, McpRequest::payload('server/discover'), McpRequest::headers('server/discover'))
        ->assertOk()
        ->json('result.instructions');

    expect($publicInstructions)->toContain('craft-context-get')
        ->not->toContain('Preserve legal notices.', 'reviews.list', 'reviews.submit');

    Route::getCurrentRoute()->flushController();
    $this->postJson($route, McpRequest::payload('tools/call', ['name' => 'info.get']), McpRequest::headers('tools/call', 'info.get'))
        ->assertBadRequest()->assertJsonPath('error.message', 'Tool not found: "info.get".');
})->with([
    'array config' => [fn (string $instructions): array => ['instructions' => $instructions]],
    'JSON config' => [fn (string $instructions): string => json_encode(['instructions' => $instructions], JSON_THROW_ON_ERROR)],
    'fluent config' => [fn (string $instructions): McpConfig => McpConfig::create()->instructions($instructions)],
]);

it('includes registered plugin element references in the admin info guidance', function (): void {
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

    $instructions = McpRequest::send($this, 'tools/call', ['name' => 'info.get'])->assertOk()->assertJsonPath('result.isError', false)->json('result.structuredContent.instructions');

    expect($instructions)->toContain('hardDelete', 'product: `'.$type::class.'`');
});
