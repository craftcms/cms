<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig;

use CraftCms\Cms\Twig\Exceptions\TemplateLoaderException;
use CraftCms\Cms\Updates\Updates;
use Twig\Loader\LoaderInterface;
use Twig\Source;

use function CraftCms\Cms\t;

readonly class TemplateLoader implements LoaderInterface
{
    public function __construct(
        private TemplateResolver $resolver,
    ) {}

    public function exists(string $name): bool
    {
        return $this->resolver->exists($name);
    }

    public function getSourceContext(string $name): Source
    {
        $template = $this->resolveTemplate($name);

        if (! is_readable($template)) {
            throw new TemplateLoaderException($name, t('Tried to read the template at {path}, but could not. Check the permissions.', ['path' => $template]));
        }

        return new Source(file_get_contents($template), $name, $template);
    }

    /**
     * Gets the cache key to use for the cache for a given template.
     *
     * @param  string  $name  The name of the template to load
     * @return string The cache key (the path to the template)
     *
     * @throws TemplateLoaderException if the template doesn’t exist
     */
    public function getCacheKey(string $name): string
    {
        return $this->resolveTemplate($name);
    }

    /**
     * Returns whether the cached template is still up to date with the latest template.
     *
     * @param  string  $name  The template name
     * @param  int  $time  The last modification time of the cached template
     *
     * @throws TemplateLoaderException if the template doesn’t exist
     */
    public function isFresh(string $name, int $time): bool
    {
        // If this is a control panel request and a DB update is needed, force a recompile.
        if (request()->isCpRequest() && app(Updates::class)->isCraftUpdatePending()) {
            return false;
        }

        $sourceModifiedTime = filemtime($this->resolveTemplate($name));

        return $sourceModifiedTime <= $time;
    }

    /**
     * Returns the path to a given template, or throws a TemplateLoaderException.
     *
     * @throws TemplateLoaderException if the template doesn’t exist
     */
    private function resolveTemplate(string $name): string
    {
        $template = $this->resolver->resolve($name);

        if ($template !== false) {
            return $template;
        }

        throw new TemplateLoaderException($name, t('Unable to find the template “{template}”.', ['template' => $name]));
    }
}
