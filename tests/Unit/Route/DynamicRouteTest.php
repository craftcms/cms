<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Route\DynamicRoute;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/craft-dynamic-route-test-'.uniqid();
    File::ensureDirectoryExists($this->tempDir);

    Aliases::set('@templates', $this->tempDir);
    TemplateMode::set(TemplateMode::Site);
    Cms::config()->headlessMode(false);
    config()->set('app.debug', false);
});

afterEach(function () {
    File::deleteDirectory($this->tempDir);
});

it('renders public blade templates from the site templates path', function () {
    file_put_contents($this->tempDir.'/blade-page.blade.php', '<p>{{ $name }}</p>');

    $response = new DynamicRoute('templates/render', ['template' => 'blade-page'])
        ->handle(Request::create('/blade-page', 'GET', ['name' => '<Rias>']));

    expect($response->getContent())->toBe('<p>&lt;Rias&gt;</p>');
});

it('does not render private templates by default', function () {
    file_put_contents($this->tempDir.'/_entry.twig', 'Private entry template');

    new DynamicRoute('templates/render', ['template' => '_entry'])
        ->handle(Request::create('/news/test-entry'));
})->throws(NotFoundHttpException::class);

it('renders private templates when explicitly allowed', function () {
    file_put_contents($this->tempDir.'/_entry.twig', 'Private entry template');

    $response = new DynamicRoute('templates/render', [
        'template' => '_entry',
        'publicOnly' => false,
    ])->handle(Request::create('/news/test-entry'));

    expect($response->getContent())->toBe('Private entry template');
});

it('does not render internal laravel views through public template routing', function () {
    new DynamicRoute('templates/render', ['template' => 'mail/system-message'])
        ->handle(Request::create('/mail/system-message', 'GET', ['htmlBody' => '<script>alert(1)</script>']));
})->throws(NotFoundHttpException::class);
