<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Twig\Twig;
use CraftCms\Cms\Twig\TwigExceptionMapper;
use CraftCms\Cms\View\TemplateManager;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\File;

it('ignores missing compiled template files', function () {
    $path = storage_path('runtime/compiled_templates/missing.php');

    @unlink($path);

    expect((new TwigExceptionMapper)->resolveTemplatePathAndLine($path, 1))->toBeFalse();
});

it('maps errors thrown in templates to their source template', function () {
    $tempDir = sys_get_temp_dir().'/craft-twig-error-test-'.uniqid();
    File::ensureDirectoryExists($tempDir);
    File::put("$tempDir/broken.twig", "Line one\n{{ 1 // 0 }}");
    Aliases::set('@templates', $tempDir);

    try {
        app(TemplateManager::class)->renderTemplate('broken', templateMode: TemplateMode::Site);
    } catch (Throwable $exception) {
        $handler = app(ExceptionHandler::class);
        $handler->reportable(function (Throwable $reported) use (&$mapped) {
            $mapped = $reported;

            return false;
        });
        $handler->report($exception);

        expect($mapped)->toBeInstanceOf(DivisionByZeroError::class)
            ->and(realpath($mapped->getFile()))->toBe(realpath("$tempDir/broken.twig"))
            ->and($mapped->getLine())->toBe(2)
            ->and(collect($mapped->getTrace())->pluck('file')->filter()->contains(fn (string $file) => str_contains($file, 'compiled_templates')))->toBeFalse();

        return;
    } finally {
        File::deleteDirectory($tempDir);
    }

    $this->fail('Expected the template to throw.');
});

it('maps errors to the template mode’s source template when another mode has a template with the same name', function () {
    $tempDir = sys_get_temp_dir().'/craft-twig-error-test-'.uniqid();
    File::ensureDirectoryExists($tempDir);
    // The control panel has a set-password template too
    File::put("$tempDir/set-password.twig", "Line one\n{{ 1 // 0 }}");
    Aliases::set('@templates', $tempDir);

    try {
        app(TemplateManager::class)->renderTemplate('set-password', templateMode: TemplateMode::Site);
    } catch (Throwable $exception) {
        $mapped = TemplateMode::with(TemplateMode::Cp, fn () => app(TwigExceptionMapper::class)->map($exception));

        expect(realpath($mapped->getFile()))->toBe(realpath("$tempDir/set-password.twig"))
            ->and($mapped->getLine())->toBe(2);

        return;
    } finally {
        File::deleteDirectory($tempDir);
    }

    $this->fail('Expected the template to throw.');
});

it('keeps string template filenames reportable', function () {
    app(Twig::class)->get(TemplateMode::Site)->enableStrictVariables();

    try {
        app(TemplateManager::class)->renderTwigString('{{ app("CraftCms\\\\Cms\\\\View\\\\TemplateManager").renderTwigString("{{ someVar }}") }}');
    } catch (Throwable $exception) {
        $trace = app(TwigExceptionMapper::class)->map($exception)->getTrace();

        $files = collect($trace)
            ->filter(fn (array $frame) => array_key_exists('file', $frame))
            ->pluck('file')
            ->all();

        expect($files)
            ->not->toBeEmpty()
            ->each->toBeString()
            ->and(collect($files)->contains(fn (string $file) => str_contains($file, '(string template ')))->toBeTrue();

        return;
    }

    $this->fail('Expected the nested string template to throw.');
});
