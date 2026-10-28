<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Cp;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Responses\BridgedScreen;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Route;
use Inertia\Support\Header;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\template;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\withHeaders;

beforeEach(function () {
    actingAs(User::findOne());
});

dataset('bridgedScreens', [
    'cp screen response' => [
        function (): string {
            Route::middleware(['craft', 'craft.web', 'craft.cp', 'auth', 'can:accessCp'])
                ->prefix(Cms::config()->cpTrigger)
                ->get('bridged-cp-screen-test', fn () => new CpScreenResponse()
                    ->title('Bridged screen')
                    ->contentHtml('<p>Server-rendered content</p>'));

            return cp_url('bridged-cp-screen-test');
        },
    ],
    'template extending _layouts/cp' => [
        function (): string {
            Route::middleware(['craft', 'craft.web', 'craft.cp', 'auth', 'can:accessCp'])
                ->prefix(Cms::config()->cpTrigger)
                ->get('bridged-screen-test', fn () => template('_special/sitepicker', ['baseUrl' => cp_url()]));

            return cp_url('bridged-screen-test');
        },
    ],
]);

it('sends a client-side visit to a bridged screen as a hard visit', function (Closure $screen) {
    $url = $screen();

    withHeaders([
        Header::INERTIA => 'true',
        Header::VERSION => (string) Cp::vite()->manifestHash(),
    ])
        ->get($url)
        ->assertConflict()
        ->assertHeader(Header::LOCATION, $url);
})->with('bridgedScreens');

it('loads deferred props on a bridged screen without a hard visit', function (Closure $screen) {
    $url = $screen();

    app(ProjectConfig::class)->set(sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, Entry::class), [
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'showBadgeCount' => true],
    ]);

    withHeaders([
        Header::INERTIA => 'true',
        Header::VERSION => (string) Cp::vite()->manifestHash(),
        Header::PARTIAL_COMPONENT => BridgedScreen::PAGE,
        Header::PARTIAL_ONLY => 'navSourceBadges',
    ])
        ->get($url)
        ->assertOk()
        ->assertJsonPath('component', BridgedScreen::PAGE)
        ->assertJsonStructure(['props' => ['navSourceBadges']]);
})->with('bridgedScreens');
