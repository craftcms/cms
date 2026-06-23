<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Route\DynamicRoute;
use CraftCms\Cms\Twig\Exceptions\TemplateLoaderException;
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

function expectTemplateLoaderExceptionRendersAs404(Closure $callback): void
{
    try {
        $callback();
    } catch (TemplateLoaderException $exception) {
        expect($exception->render(Request::create('/'))?->getStatusCode())->toBe(404);

        return;
    }

    test()->fail('Expected TemplateLoaderException was not thrown.');
}

it('does not render private templates by default', function () {
    file_put_contents($this->tempDir.'/_entry.twig', 'Private entry template');

    expectTemplateLoaderExceptionRendersAs404(fn () => new DynamicRoute('templates/render', ['template' => '_entry'])
        ->handle(Request::create('/news/test-entry')));
});

it('renders private templates when explicitly allowed', function () {
    file_put_contents($this->tempDir.'/_entry.twig', 'Private entry template');

    $response = new DynamicRoute('templates/render', [
        'template' => '_entry',
        'publicOnly' => false,
    ])->handle(Request::create('/news/test-entry'));

    expect($response->getContent())->toBe('Private entry template');
});

it('renders Blade templates through the Craft renderer', function () {
    file_put_contents($this->tempDir.'/entry.blade.php', <<<'BLADE'
<html>
<head>@craftHead</head>
<body>
@craftEndBody
@craftJs('console.log("route");')
Blade route: {{ $title }}
</body>
</html>
BLADE);

    $response = new DynamicRoute('templates/render', [
        'template' => 'entry',
        'variables' => ['title' => 'Hello'],
    ])->handle(Request::create('/entry'));

    expect($response->getContent())
        ->toContain('Blade route: Hello')
        ->toContain('console.log("route");')
        ->not->toContain('CRAFT-BLOCK-BODY-END');
});

it('does not render private Blade templates by default', function () {
    file_put_contents($this->tempDir.'/_entry.blade.php', 'Private Blade entry template');

    expectTemplateLoaderExceptionRendersAs404(fn () => new DynamicRoute('templates/render', ['template' => '_entry'])
        ->handle(Request::create('/news/test-entry')));
});

it('does not render Blade templates in headless mode', function () {
    Cms::config()->headlessMode(true);
    file_put_contents($this->tempDir.'/entry.blade.php', 'Blade entry template');

    new DynamicRoute('templates/render', ['template' => 'entry'])
        ->handle(Request::create('/news/test-entry'));
})->throws(NotFoundHttpException::class);

it('does not render internal laravel views through public template routing', function () {
    expectTemplateLoaderExceptionRendersAs404(fn () => new DynamicRoute('templates/render', ['template' => 'mail/system-message'])
        ->handle(Request::create('/mail/system-message', 'GET', ['htmlBody' => '<script>alert(1)</script>'])));
});
