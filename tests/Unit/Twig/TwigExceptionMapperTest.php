<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Twig\Twig;
use CraftCms\Cms\Twig\TwigExceptionMapper;
use CraftCms\Cms\View\TemplateManager;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
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
        $response = app(ExceptionHandler::class)->render(Request::create('/', server: ['HTTP_ACCEPT' => 'application/json']), $exception);
        $data = Json::decode((string) $response->getContent());

        expect($data['exception'])->toBe(DivisionByZeroError::class)
            ->and($data['file'])->toBe("$tempDir/broken.twig")
            ->and($data['line'])->toBe(2)
            ->and(collect($data['trace'])->pluck('file')->filter()->contains(fn (string $file) => str_contains($file, 'compiled_templates')))->toBeFalse();

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
