<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig;

use craft\helpers\FileHelper;
use craft\helpers\Path;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Facades\Log;
use Twig\Error\LoaderError;

use function CraftCms\Cms\t;
use function Illuminate\Filesystem\join_paths;

#[Scoped]
class TemplateResolver
{
    /** @var string[] Resolved template path cache (request-scoped) */
    private array $templatePaths = [];

    /**
     * Returns whether a template exists.
     *
     * Internally, this will just call [[resolve()]] with the given template name, and return whether that
     * method found anything.
     *
     * @param  string  $name  The name of the template.
     * @param  ?TemplateMode  $templateMode  The template mode to use.
     * @param  bool  $publicOnly  Whether to only look for public templates (template paths that don’t start with the private template trigger).
     * @return bool Whether the template exists.
     */
    public function exists(string $name, ?TemplateMode $templateMode = null, bool $publicOnly = false): bool
    {
        try {
            return $this->resolve($name, $templateMode, $publicOnly) !== false;
        } catch (LoaderError) {
            // _validateTemplateName() had an issue with it

            return false;
        }
    }

    /**
     * Finds a template on the file system and returns its path.
     *
     * All of the following files will be searched for, in this order:
     *
     * - TemplateName
     * - TemplateName.html
     * - TemplateName.twig
     * - TemplateName/index.html
     * - TemplateName/index.twig
     *
     * If this is a front-end request, the actual list of file extensions and
     * index filenames are configurable via the <config5:defaultTemplateExtensions>
     * and <config5:indexTemplateFilenames> config settings.
     *
     * For example if you set the following in config/general.php:
     *
     * ```php
     * 'defaultTemplateExtensions' => ['htm'],
     * 'indexTemplateFilenames' => ['default'],
     * ```
     *
     * then the following files would be searched for instead:
     *
     * - TemplateName
     * - TemplateName.htm
     * - TemplateName/default.htm
     *
     * The actual directory that those files will depend on the current [[setTemplateMode()|template mode]]
     * (probably `templates/` if it’s a front-end site request, and `vendor/craftcms/cms/resources/templates/` if it’s a Control
     * Panel request).
     *
     * If this is a front-end site request, a folder named after the current site handle will be checked first.
     *
     * - templates/SiteHandle/...
     * - templates/...
     *
     * And finally, if this is a control panel request _and_ the template name includes multiple segments _and_ the first
     * segment of the template name matches a plugin’s handle, then Craft will look for a template named with the
     * remaining segments within that plugin’s templates/ subfolder.
     *
     * To put it all together, here’s where Craft would look for a template named “foo/bar”, depending on the type of
     * request it is:
     *
     * - Front-end site requests:
     *     - templates/SiteHandle/foo/bar
     *     - templates/SiteHandle/foo/bar.html
     *     - templates/SiteHandle/foo/bar.twig
     *     - templates/SiteHandle/foo/bar/index.html
     *     - templates/SiteHandle/foo/bar/index.twig
     *     - templates/foo/bar
     *     - templates/foo/bar.html
     *     - templates/foo/bar.twig
     *     - templates/foo/bar/index.html
     *     - templates/foo/bar/index.twig
     * - Control panel requests:
     *     - vendor/craftcms/cms/src/templates/foo/bar
     *     - vendor/craftcms/cms/src/templates/foo/bar.html
     *     - vendor/craftcms/cms/src/templates/foo/bar.twig
     *     - vendor/craftcms/cms/src/templates/foo/bar/index.html
     *     - vendor/craftcms/cms/src/templates/foo/bar/index.twig
     *     - path/to/fooplugin/templates/bar
     *     - path/to/fooplugin/templates/bar.html
     *     - path/to/fooplugin/templates/bar.twig
     *     - path/to/fooplugin/templates/bar/index.html
     *     - path/to/fooplugin/templates/bar/index.twig
     *
     * @param  string  $name  The name of the template.
     * @param  ?TemplateMode  $templateMode  The template mode to use.
     * @param  bool  $publicOnly  Whether to only look for public templates (template paths that don’t start with the private template trigger).
     * @return string|false The path to the template if it exists, or `false`.
     *
     * @throws LoaderError
     */
    public function resolve(string $name, ?TemplateMode $templateMode = null, bool $publicOnly = false): string|false
    {
        return TemplateMode::with($templateMode ?? TemplateMode::get(), fn () => $this->resolveInternal($name, $publicOnly));
    }

    /**
     * Finds a template on the file system and returns its path.
     *
     * @param  string  $name  The name of the template.
     * @param  bool  $publicOnly  Whether to only look for public templates (template paths that don’t start with the private template trigger).
     * @return string|false The path to the template if it exists, or `false`.
     *
     * @throws LoaderError
     */
    private function resolveInternal(string $name, bool $publicOnly): string|false
    {
        // Normalize the template name
        $name = trim((string) preg_replace('#/{2,}#', '/', str_replace('\\', '/', Str::convertToUtf8($name))), '/');

        $key = TemplateMode::get()->templatesPath().':'.$name;

        // Is this template path already cached?
        if (isset($this->templatePaths[$key])) {
            return $this->templatePaths[$key];
        }

        // Validate the template name
        $this->validateTemplateName($name);

        // Look for the template in the main templates folder
        $basePaths = [];

        // Should we be looking for a localized version of the template?
        if (TemplateMode::is(TemplateMode::Site) && Cms::isInstalled()) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $sitePath = join_paths(TemplateMode::get()->templatesPath(), Sites::getCurrentSite()->handle);
            if (is_dir($sitePath)) {
                $basePaths[] = $sitePath;
            }
        }

        $basePaths[] = TemplateMode::get()->templatesPath();

        foreach ($basePaths as $basePath) {
            if (($path = $this->resolveFromPath($basePath, $name, $publicOnly)) !== null) {
                return $this->templatePaths[$key] = $path;
            }
        }

        unset($basePaths);

        // Check any registered template roots
        $roots = TemplateMode::get()->templateRoots();

        foreach ($roots as $templateRoot => $basePaths) {
            /** @var string[] $basePaths */
            $templateRootLen = strlen((string) $templateRoot);
            if ($templateRoot === '' || strncasecmp($templateRoot.'/', $name.'/', $templateRootLen + 1) === 0) {
                $subName = $templateRoot === '' ? $name : (strlen($name) === $templateRootLen ? '' : substr($name, $templateRootLen + 1));
                foreach ($basePaths as $basePath) {
                    if (($path = $this->resolveFromPath($basePath, $subName, $publicOnly)) !== null) {
                        return $this->templatePaths[$key] = $path;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Searches for template files, and returns the first match if there is one.
     *
     * @param  string  $basePath  The base path to be looking in.
     * @param  string  $name  The name of the template to be looking for.
     * @param  bool  $publicOnly  Whether to only look for public templates (template paths that don’t start with the private template trigger).
     * @return string|null The matching file path, or `null`.
     */
    private function resolveFromPath(string $basePath, string $name, bool $publicOnly): ?string
    {
        $templateMode = TemplateMode::get();

        // Normalize the path and name
        $basePath = FileHelper::normalizePath($basePath);
        $name = trim(FileHelper::normalizePath($name), '/');

        // $name could be an empty string (e.g. to load the homepage template)
        if ($name !== '') {
            if ($publicOnly && preg_match(sprintf('/(^|\/)%s/', preg_quote($templateMode->privateTemplateTrigger(), '/')), $name)) {
                return null;
            }

            // Maybe $name is already the full file path
            $testPath = join_paths($basePath, $name);

            if (is_file($testPath)) {
                return $testPath;
            }

            foreach ($templateMode->defaultTemplateExtensions() as $extension) {
                $testPath = join_paths($basePath, $name.'.'.$extension);

                if (is_file($testPath)) {
                    return $testPath;
                }
            }
        }

        foreach ($templateMode->indexTemplateFilenames() as $filename) {
            foreach ($templateMode->defaultTemplateExtensions() as $extension) {
                $testPath = $name !== ''
                    ? join_paths($basePath, $name, $filename.'.'.$extension)
                    : join_paths($basePath, $filename.'.'.$extension);

                if (is_file($testPath)) {
                    return $testPath;
                }
            }
        }

        return null;
    }

    /**
     * Ensures that a template name isn't null and that it doesn't lead outside the template folder.
     *
     * @throws LoaderError
     */
    private function validateTemplateName(string $name): void
    {
        if (str_contains($name, "\0")) {
            throw new LoaderError(t('A template name cannot contain NUL bytes.'));
        }

        if (Path::ensurePathIsContained($name) === false) {
            Log::info('Someone tried to load a template outside the templates folder: '.$name);

            throw new LoaderError(t('Looks like you are trying to load a template outside the template folder.'));
        }
    }
}
