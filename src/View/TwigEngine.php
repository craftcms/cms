<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Support\Facades\Template;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Twig\Exceptions\TemplateLoaderException;
use Illuminate\Contracts\View\Engine;

class TwigEngine implements Engine
{
    /** @param array<string, mixed> $data */
    public function get($path, array $data = []): string
    {
        $template = $this->templateFromPath($path);

        try {
            /** @throws TemplateLoaderException */
            return Template::renderPageTemplate($template, $data, renderer: TemplateEngine::Twig);
        } catch (TemplateLoaderException $e) {
            /**
             * If a custom error page is set up on the frontend, Laravel will
             * try to render it in the Control Panel as well. This ensures
             * we render the Control Panel error templates instead.
             */
            if (TemplateMode::is(TemplateMode::Cp) && Str::contains($template, 'errors/')) {
                $template = $this->templateFromPath($path, TemplateMode::Site);

                return Template::renderPageTemplate(
                    $template,
                    $data,
                    TemplateMode::Site,
                    renderer: TemplateEngine::Twig,
                );
            }

            throw $e;
        }
    }

    private function templateFromPath(string $path, ?TemplateMode $templateMode = null): string
    {
        $templateMode ??= TemplateMode::get();

        $template = $this->stripBasePath($path, $templateMode->templatesPath());

        if ($template !== null) {
            return "/{$template}";
        }

        foreach ($templateMode->templateRoots() as $templateRoot => $basePaths) {
            foreach ($basePaths as $basePath) {
                $template = $this->stripBasePath($path, $basePath);

                if ($template !== null) {
                    return trim("{$templateRoot}/{$template}", '/');
                }
            }
        }

        return File::normalizePath($path, '/');
    }

    /**
     * Strips the templates base path from the given absolute path.
     *
     * Both paths are normalized to Twig-style separators first so that mixed
     * directory separators (e.g. `/` vs `\` on Windows) and inconsistent
     * drive-letter casing (e.g. `C:` vs `c:`) don’t cause the lookup to fall
     * through.
     */
    private function stripBasePath(string $path, string $basePath): ?string
    {
        $path = File::normalizePath($path, '/');
        $basePath = rtrim(File::normalizePath($basePath, '/'), '/');

        if ($path === $basePath) {
            return '';
        }

        if (str_starts_with($path, "{$basePath}/")) {
            return substr($path, strlen($basePath) + 1);
        }

        $lowerPath = strtolower($path);
        $lowerBasePath = strtolower($basePath);

        if ($lowerPath === $lowerBasePath) {
            return '';
        }

        if (str_starts_with($lowerPath, "{$lowerBasePath}/")) {
            return substr($path, strlen($basePath) + 1);
        }

        return null;
    }
}
