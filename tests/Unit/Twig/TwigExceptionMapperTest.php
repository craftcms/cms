<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\TwigExceptionMapper;

it('ignores missing compiled template files', function () {
    $path = storage_path('runtime/compiled_templates/missing.php');

    @unlink($path);

    expect((new TwigExceptionMapper)->resolveTemplatePathAndLine($path, 1))->toBeFalse();
});
