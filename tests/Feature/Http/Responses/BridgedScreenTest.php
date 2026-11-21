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
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateRoots;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Inertia\Support\Header;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\pageTemplate;
use function CraftCms\Cms\template;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withHeaders;

beforeEach(function () {
    actingAs(User::findOne());
});

it('preserves the header fragments of a Twig page in the Inertia shell', function () {
    app(TemplateRoots::class)->register(TemplateMode::Cp, 'test-plugin', dirname(__DIR__, 3).'/Support/templates');

    Route::middleware(['craft', 'craft.web', 'craft.cp', 'auth', 'can:accessCp'])
        ->prefix(Cms::config()->cpTrigger)
        ->get('bridged-header-test', fn () => pageTemplate('test-plugin/bridged-header'));

    get(cp_url('bridged-header-test'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component(BridgedScreen::PAGE)
            ->where('title', 'Test')
            ->where('actionButton', function (string $html): bool {
                expect($html)->toContainTag('a', ['href' => cp_url('test-plugin/new')]);

                return true;
            })
            ->where('contextMenu', fn (string $html): bool => str_contains($html, 'Choose site'))
            ->where('actionMenu', fn (string $html): bool => str_contains($html, 'More actions'))
            ->where('content', fn (string $html): bool => str_contains($html, '<p>hi</p>')));
});

it('declares no form actions on a bridged Twig form that has none', function () {
    app(TemplateRoots::class)->register(TemplateMode::Cp, 'test-plugin', dirname(__DIR__, 3).'/Support/templates');

    Route::middleware(['craft', 'craft.web', 'craft.cp', 'auth', 'can:accessCp'])
        ->prefix(Cms::config()->cpTrigger)
        ->get('bridged-form-test', fn () => pageTemplate('test-plugin/bridged-header', ['fullPageForm' => true]));

    get(cp_url('bridged-form-test'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component(BridgedScreen::PAGE)
            ->where('fullPageForm', true)
            ->where('mainFormAttributes.data.saveshortcut', true)
            ->where('mainFormAttributes.data.actions', false));
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

it('encrypts a bridged screen’s save shortcut redirect', function () {
    Route::middleware(['craft', 'craft.web', 'craft.cp', 'auth', 'can:accessCp'])
        ->prefix(Cms::config()->cpTrigger)
        ->get('bridged-save-shortcut-test', fn () => new CpScreenResponse()
            ->title('Bridged screen')
            ->action('test/save')
            ->saveShortcutRedirectUrl('test/{id}')
            ->contentHtml('<p>Fields</p>'));

    get(cp_url('bridged-save-shortcut-test'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component(BridgedScreen::PAGE)
            ->where('saveShortcutRedirect', fn (string $value): bool => Crypt::decrypt($value) === 'test/{id}'));
});
