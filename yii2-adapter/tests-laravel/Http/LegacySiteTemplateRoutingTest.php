<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Twig\TemplateResolver;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Once;

it('resolves legacy site templates from the base templates directory after resource views', function() {
    $templatesPath = base_path('templates');
    $createdTemplatesPath = !is_dir($templatesPath);
    $resourcesPath = resource_path('views');

    File::ensureDirectoryExists($templatesPath);
    File::put("$templatesPath/legacy-fallback.twig", 'legacy-template-route');
    File::put("$templatesPath/shared.twig", 'legacy-shared-route');
    File::put("$resourcesPath/shared.twig", 'resource-shared-route');
    Cms::setIsInstalled(false);
    Once::flush();

    try {
        $resolver = app(TemplateResolver::class);

        expect(TemplateMode::Site->templatesPath())->toBe($resourcesPath)
            ->and($resolver->resolve('legacy-fallback', TemplateMode::Site, publicOnly: true))->toBe("$templatesPath/legacy-fallback.twig")
            ->and($resolver->resolve('shared', TemplateMode::Site, publicOnly: true))->toBe("$resourcesPath/shared.twig");
    } finally {
        File::delete("$templatesPath/legacy-fallback.twig");
        File::delete("$templatesPath/shared.twig");
        File::delete("$resourcesPath/shared.twig");

        if ($createdTemplatesPath) {
            File::deleteDirectory($templatesPath);
        }
    }
});
