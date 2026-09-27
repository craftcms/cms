<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Auth\LoginController;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\action_url;
use function Pest\Laravel\get;

describe('renderViewWithFallback', function () {
    test('username prop is empty when rememberUsernameDuration is disabled', function () {
        Cms::config()->rememberUsernameDuration = 0;

        get(action([LoginController::class, 'showLogin']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('username', '')
            );
    });

    describe('front-end requests', function () {
        beforeEach(function () {
            Cms::config()->loginPath = 'member/login';

            Route::middleware(['web', 'craft', 'craft.web'])->group(dirname(__DIR__, 5).'/routes/web.php');

            $this->templatesDir = sys_get_temp_dir().'/craft-auth-fallback-test-'.uniqid();
            File::ensureDirectoryExists($this->templatesDir);
            Aliases::set('@templates', $this->templatesDir);
            TemplateMode::set(TemplateMode::Site);
        });

        afterEach(function () {
            File::deleteDirectory($this->templatesDir);
        });

        test('renders the site template for the requested path when one exists', function () {
            File::ensureDirectoryExists($this->templatesDir.'/member');
            File::put($this->templatesDir.'/member/login.twig', 'site-login-template {{ action }}');

            get('/member/login')
                ->assertOk()
                ->assertSeeText('site-login-template '.action([LoginController::class, 'attemptLogin']));
        });

        test('falls back to the Inertia page with shared props when no site template exists', function () {
            get('/member/login')
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->component('auth/Login')
                    ->where('action', action_url('users/login'))
                    ->where('craft.actionUrl', action_url())
                );
        });
    });
});
