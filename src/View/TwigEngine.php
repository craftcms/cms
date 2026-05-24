<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Twig\Exceptions\TemplateLoaderException;
use CraftCms\Cms\Twig\TemplateRenderer;
use Illuminate\Contracts\View\Engine;

class TwigEngine implements Engine
{
    public function get($path, array $data = []): string
    {
        $template = $this->stripBasePath($path, TemplateMode::get()->templatesPath());
        $renderer = app(TemplateRenderer::class);

        try {
            return $renderer->renderPageTemplate($template, $data);
        } catch (TemplateLoaderException $e) {
            /**
             * If a custom error page is set up on the frontend, Laravel will
             * try to render it in the Control Panel as well. This ensures
             * we render the Control Panel error templates instead.
             */
            if (TemplateMode::is(TemplateMode::Cp) && Str::contains($template, 'errors/')) {
                $template = $this->stripBasePath($path, TemplateMode::Site->templatesPath());

                return $renderer->renderPageTemplate($template, $data);
            }

            throw $e;
        }
    }

    /**
     * Strips the templates base path from the given absolute path.
     *
     * Both paths are normalized first so that mixed directory separators
     * (e.g. `/` vs `\` on Windows) and inconsistent drive-letter casing
     * (e.g. `C:` vs `c:`) don’t cause the lookup to fall through.
     */
    private function stripBasePath(string $path, string $basePath): string
    {
        $path = File::normalizePath($path);
        $basePath = File::normalizePath($basePath);

        $template = Str::after($path, $basePath);

        // Fallback for case-insensitive filesystems (e.g. Windows drive letters).
        if ($template === $path) {
            return Str::after(strtolower($path), strtolower($basePath));
        }

        return $template;
    }
}
