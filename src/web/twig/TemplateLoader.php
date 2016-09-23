<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig;

use Craft;
use craft\app\helpers\Io;
use craft\app\web\View;

/**
 * Loads Craft templates into Twig.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */

/** @noinspection PhpDeprecationInspection */
class TemplateLoader implements \Twig_LoaderInterface, \Twig_ExistsLoaderInterface
{
    // Properties
    // =========================================================================

    /**
     * @var View
     */
    protected $view;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct(View $view)
    {
        $this->view = $view;
    }

    /**
     * Checks if a template exists.
     *
     * @param string $name
     *
     * @return boolean
     */
    public function exists($name)
    {
        return $this->view->doesTemplateExist($name);
    }

    /**
     * Gets the source code of a template.
     *
     * @param  string $name The name of the template to load, or a StringTemplate object.
     *
     * @return string|StringTemplate The template source code.
     * @throws TemplateLoaderException if the template doesn’t exist or isn’t readable
     */
    public function getSource($name)
    {
        if ($name instanceof StringTemplate) {
            return $name->template;
        }

        $template = $this->_resolveTemplate($name);

        if (Io::isReadable($template)) {
            return Io::getFileContents($template);
        }

        throw new TemplateLoaderException($name, Craft::t('app', 'Tried to read the template at {path}, but could not. Check the permissions.', ['path' => $template]));
    }

    /**
     * Gets the cache key to use for the cache for a given template.
     *
     * @param StringTemplate|string $name The name of the template to load, or a StringTemplate object.
     *
     * @return string The cache key (the path to the template)
     * @throws TemplateLoaderException if the template doesn’t exist
     */
    public function getCacheKey($name)
    {
        if ($name instanceof StringTemplate) {
            return $name->cacheKey;
        }

        return $this->_resolveTemplate($name);
    }

    /**
     * Returns whether the cached template is still up-to-date with the latest template.
     *
     * @param string  $name The template name, or a StringTemplate object.
     * @param integer $time The last modification time of the cached template
     *
     * @return boolean
     * @throws TemplateLoaderException if the template doesn’t exist
     */
    public function isFresh($name, $time)
    {
        // If this is a CP request and a DB update is needed, force a recompile.
        $request = Craft::$app->getRequest();

        if (!$request->getIsConsoleRequest() && $request->getIsCpRequest() && Craft::$app->getIsUpdating()) {
            return false;
        }

        if (is_string($name)) {
            $sourceModifiedTime = Io::getLastTimeModified($this->_resolveTemplate($name));

            return $sourceModifiedTime->getTimestamp() <= $time;
        }

        return false;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the path to a given template, or throws a TemplateLoaderException.
     *
     * @param $name
     *
     * @return string
     * @throws TemplateLoaderException if the template doesn’t exist
     */
    private function _resolveTemplate($name)
    {
        $template = $this->view->resolveTemplate($name);

        if ($template !== false) {
            return $template;
        }

        throw new TemplateLoaderException($name, Craft::t('app', 'Unable to find the template “{template}”.', ['template' => $name]));
    }
}
