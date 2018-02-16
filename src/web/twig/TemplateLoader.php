<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig;

use Craft;
use craft\web\View;

/**
 * Loads Craft templates into Twig.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */

/** @noinspection PhpDeprecationInspection */
class TemplateLoader implements \Twig_LoaderInterface, \Twig_ExistsLoaderInterface
{
    // Properties
    // =========================================================================

    /**
     * @var View|null
     */
    protected $view;

    // Public Methods
    // =========================================================================

    /**
     * Constructor
     *
     * @param View $view
     */
    public function __construct(View $view)
    {
        $this->view = $view;
    }

    /**
     * @inheritdoc
     */
    public function exists($name)
    {
        return $this->view->doesTemplateExist($name);
    }

    /**
     * @inheritdoc
     */
    public function getSourceContext($name)
    {
        $template = $this->_resolveTemplate($name);

        if (!is_readable($template)) {
            throw new TemplateLoaderException($name, Craft::t('app', 'Tried to read the template at {path}, but could not. Check the permissions.', ['path' => $template]));
        }

        return new \Twig_Source(file_get_contents($template), $name, $template);
    }

    /**
     * Gets the cache key to use for the cache for a given template.
     *
     * @param string $name The name of the template to load
     * @return string The cache key (the path to the template)
     * @throws TemplateLoaderException if the template doesn’t exist
     */
    public function getCacheKey($name): string
    {
        return $this->_resolveTemplate($name);
    }

    /**
     * Returns whether the cached template is still up-to-date with the latest template.
     *
     * @param string $name The template name
     * @param int $time The last modification time of the cached template
     * @return bool
     * @throws TemplateLoaderException if the template doesn’t exist
     */
    public function isFresh($name, $time): bool
    {
        // If this is a CP request and a DB update is needed, force a recompile.
        $request = Craft::$app->getRequest();

        if ($request->getIsCpRequest() && Craft::$app->getUpdates()->getIsCraftDbMigrationNeeded()) {
            return false;
        }

        if (is_string($name)) {
            $sourceModifiedTime = filemtime($this->_resolveTemplate($name));

            return $sourceModifiedTime <= $time;
        }

        return false;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the path to a given template, or throws a TemplateLoaderException.
     *
     * @param string $name
     * @return string
     * @throws TemplateLoaderException if the template doesn’t exist
     */
    private function _resolveTemplate(string $name): string
    {
        $template = $this->view->resolveTemplate($name);

        if ($template !== false) {
            return $template;
        }

        throw new TemplateLoaderException($name, Craft::t('app', 'Unable to find the template “{template}”.', ['template' => $name]));
    }
}
