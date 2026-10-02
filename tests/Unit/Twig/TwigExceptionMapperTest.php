<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Twig;
use CraftCms\Cms\Twig\TwigExceptionMapper;
use CraftCms\Cms\View\TemplateManager;
use CraftCms\Cms\View\TemplateMode;

it('ignores missing compiled template files', function () {
    $path = storage_path('runtime/compiled_templates/missing.php');

    @unlink($path);

    expect((new TwigExceptionMapper)->resolveTemplatePathAndLine($path, 1))->toBeFalse();
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
            ->and(collect($files)->contains(fn (string $file) => str_starts_with($file, '__string_template__')))->toBeTrue();

        return;
    }

    $this->fail('Expected the nested string template to throw.');
});
