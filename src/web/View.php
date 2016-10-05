<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web;

use Craft;
use craft\app\base\Element;
use craft\app\events\Event;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\ElementHelper;
use craft\app\helpers\Html as HtmlHelper;
use craft\app\helpers\Io;
use craft\app\helpers\Json;
use craft\app\helpers\Path;
use craft\app\helpers\StringHelper;
use craft\app\services\Plugins;
use craft\app\web\assets\AppAsset;
use craft\app\web\twig\Environment;
use craft\app\web\twig\Extension;
use craft\app\web\twig\Parser;
use craft\app\web\twig\StringTemplate;
use craft\app\web\twig\Template;
use craft\app\web\twig\TemplateLoader;
use yii\base\Exception;
use yii\helpers\Html;
use yii\web\AssetBundle;

/**
 * @inheritdoc
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class View extends \yii\web\View
{
    // Constants
    // =========================================================================

    /**
     * @const TEMPLATE_MODE_CP
     */
    const TEMPLATE_MODE_CP = 'cp';

    /**
     * @const TEMPLATE_MODE_SITE
     */
    const TEMPLATE_MODE_SITE = 'site';

    // Properties
    // =========================================================================

    /**
     * @var array The sizes that element thumbnails should be rendered in
     */
    private static $_elementThumbSizes = [30, 60, 100, 200];

    /**
     * @var
     */
    private $_twigs;

    /**
     * @var
     */
    private $_twigOptions;

    /**
     * @var
     */
    private $_templatePaths;

    /**
     * @var
     */
    private $_objectTemplates;

    /**
     * @var string
     */
    private $_templateMode;

    /**
     * @var string The root path to look for templates in
     */
    private $_templatesPath;

    /**
     * @var
     */
    private $_defaultTemplateExtensions;

    /**
     * @var
     */
    private $_indexTemplateFilenames;

    /**
     * @var
     */
    private $_namespace;

    /**
     * @var array
     */
    private $_jsBuffers = [];

    /**
     * @var array
     */
    private $_translations = [];

    /**
     * @var
     */
    private $_hooks;

    /**
     * @var
     */
    private $_textareaMarkers;

    /**
     * @var
     */
    private $_renderingTemplate;

    /**
     * @var
     */
    private $_isRenderingPageTemplate = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Set the initial template mode based on whether this is a CP or Site request
        $request = Craft::$app->getRequest();
        if (!$request->getIsConsoleRequest() && $request->getIsCpRequest()) {
            $this->setTemplateMode(self::TEMPLATE_MODE_CP);
        } else {
            $this->setTemplateMode(self::TEMPLATE_MODE_SITE);
        }

        // Register the cp.elements.element hook
        $this->hook('cp.elements.element', [$this, '_getCpElementHtml']);
    }

    /**
     * Returns the Twig Environment instance for a given template loader class.
     *
     * @param string $loaderClass The name of the class that should be initialized as the Twig instance’s template
     *                            loader. If no class is passed in, [[TemplateLoader]] will be used.
     * @param array  $options     Options to instantiate Twig with
     *
     * @return Environment The Twig Environment instance.
     */
    public function getTwig($loaderClass = null, $options = [])
    {
        if (!$loaderClass) {
            $loaderClass = \craft\app\web\twig\TemplateLoader::class;
        }

        $cacheKey = $loaderClass.':'.md5(serialize($options));

        if (!isset($this->_twigs[$cacheKey])) {
            /** @var $loader TemplateLoader */
            if ($loaderClass === \craft\app\web\twig\TemplateLoader::class) {
                $loader = new $loaderClass($this);
            } else {
                $loader = new $loaderClass();
            }

            $options = array_merge($this->_getTwigOptions(), $options);

            $twig = new Environment($loader, $options);

            $twig->addExtension(new \Twig_Extension_StringLoader());
            $twig->addExtension(new Extension($this, $twig));

            if (Craft::$app->getConfig()->get('devMode')) {
                $twig->addExtension(new \Twig_Extension_Debug());
            }

            // Set our timezone
            /** @var \Twig_Extension_Core $core */
            $core = $twig->getExtension('core');
            $timezone = Craft::$app->getTimeZone();
            $core->setTimezone($timezone);

            // Set our custom parser to support resource registration tags using the capture mode
            $twig->setParser(new Parser($twig));

            $this->_twigs[$cacheKey] = $twig;

            // Give plugins a chance to add their own Twig extensions
            $this->_addPluginTwigExtensions($twig);
        }

        return $this->_twigs[$cacheKey];
    }

    /**
     * Returns whether a template is currently being rendered.
     *
     * @return boolean Whether a template is currently being rendered.
     */
    public function getIsRenderingTemplate()
    {
        return isset($this->_renderingTemplate);
    }

    /**
     * Returns the template path that is currently being rendered, or the full template if [[renderString()]] or
     * [[renderObjectTemplate()]] was called.
     *
     * @return mixed The template that is being rendered.
     */
    public function getRenderingTemplate()
    {
        if ($this->getIsRenderingTemplate()) {
            if (strncmp($this->_renderingTemplate, 'string:', 7) === 0) {
                $template = $this->_renderingTemplate;
            } else {
                $template = $this->resolveTemplate($this->_renderingTemplate);

                if (!$template) {
                    $template = $this->_templatesPath.'/'.$this->_renderingTemplate;
                }
            }

            return $template;
        }

        return null;
    }

    /**
     * Returns whether a page template is currently being rendered
     */

    /**
     * Renders a Twig template.
     *
     * @param mixed   $template  The name of the template to load, or a StringTemplate object.
     * @param array   $variables The variables that should be available to the template.
     *
     * @return string the rendering result
     * @throws \Twig_Error_Loader if the template doesn’t exist
     */
    public function renderTemplate($template, $variables = [])
    {
        Craft::trace("Rendering template: $template", __METHOD__);

        // Render and return
        $renderingTemplate = $this->_renderingTemplate;
        $this->_renderingTemplate = $template;
        Craft::beginProfile($template, __METHOD__);
        $output = $this->getTwig()->render($template, $variables);
        Craft::endProfile($template, __METHOD__);
        $this->_renderingTemplate = $renderingTemplate;

        return $output;
    }

    /**
     * Renders a Twig template that represents an entire web page.
     *
     * @param mixed $template  The name of the template to load, or a StringTemplate object.
     * @param array $variables The variables that should be available to the template.
     *
     * @return string the rendering result
     */
    public function renderPageTemplate($template, $variables = [])
    {
        ob_start();
        ob_implicit_flush(false);

        $isRenderingPageTemplate = $this->_isRenderingPageTemplate;
        $this->_isRenderingPageTemplate = true;

        $this->beginPage();
        echo $this->renderTemplate($template, $variables);
        $this->endPage();

        $this->_isRenderingPageTemplate = $isRenderingPageTemplate;

        return ob_get_clean();
    }

    /**
     * Renders a macro within a given Twig template.
     *
     * @param string $template The name of the template the macro lives in.
     * @param string $macro    The name of the macro.
     * @param array  $args     Any arguments that should be passed to the macro.
     *
     * @return string The rendered macro output.
     */
    public function renderTemplateMacro($template, $macro, $args = [])
    {
        $twig = $this->getTwig();
        $twigTemplate = $twig->loadTemplate($template);

        $renderingTemplate = $this->_renderingTemplate;
        $this->_renderingTemplate = $template;
        $output = call_user_func_array([$twigTemplate, 'get'.$macro], $args);
        $this->_renderingTemplate = $renderingTemplate;

        return (string)$output;
    }

    /**
     * Renders a template defined in a string.
     *
     * @param string  $template  The source template string.
     * @param array   $variables Any variables that should be available to the template.
     *
     * @return string The rendered template.
     */
    public function renderString($template, $variables = [])
    {
        $stringTemplate = new StringTemplate(md5($template), $template);

        $lastRenderingTemplate = $this->_renderingTemplate;
        $this->_renderingTemplate = 'string:'.$template;
        $result = $this->renderTemplate($stringTemplate, $variables);
        $this->_renderingTemplate = $lastRenderingTemplate;

        return $result;
    }

    /**
     * Renders a micro template for accessing properties of a single object.
     *
     * The template will be parsed for {variables} that are delimited by single braces, which will get replaced with
     * full Twig output tags, i.e. {{ object.variable }}. Regular Twig tags are also supported.
     *
     * @param string  $template The source template string.
     * @param mixed   $object   The object that should be passed into the template.
     *
     * @return string The rendered template.
     */
    public function renderObjectTemplate($template, $object)
    {
        // If there are no dynamic tags, just return the template
        if (!StringHelper::contains($template, '{')) {
            return $template;
        }

        // Get a Twig instance with the String template loader
        $twig = $this->getTwig('Twig_Loader_String');

        // Have we already parsed this template?
        if (!isset($this->_objectTemplates[$template])) {
            // Replace shortcut "{var}"s with "{{object.var}}"s, without affecting normal Twig tags
            $formattedTemplate = preg_replace('/(?<![\{\%])\{(?![\{\%])/', '{{object.', $template);
            $formattedTemplate = preg_replace('/(?<![\}\%])\}(?![\}\%])/', '|raw}}', $formattedTemplate);
            $this->_objectTemplates[$template] = $twig->loadTemplate($formattedTemplate);
        }

        // Temporarily disable strict variables if it's enabled
        $strictVariables = $twig->isStrictVariables();

        if ($strictVariables) {
            $twig->disableStrictVariables();
        }

        // Render it!
        $lastRenderingTemplate = $this->_renderingTemplate;
        $this->_renderingTemplate = 'string:'.$template;
        /** @var Template $templateObj */
        $templateObj = $this->_objectTemplates[$template];
        $output = $templateObj->render([
            'object' => $object
        ]);

        $this->_renderingTemplate = $lastRenderingTemplate;

        // Re-enable strict variables
        if ($strictVariables) {
            $twig->enableStrictVariables();
        }

        return $output;
    }

    /**
     * Returns whether a template exists.
     *
     * Internally, this will just call [[resolveTemplate()]] with the given template name, and return whether that
     * method found anything.
     *
     * @param string $name The name of the template.
     *
     * @return boolean Whether the template exists.
     */
    public function doesTemplateExist($name)
    {
        try {
            return ($this->resolveTemplate($name) !== false);
        } catch (\Twig_Error_Loader $e) {
            // _validateTemplateName() han an issue with it
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
     * If this is a front-end request, the actual list of file extensions and index filenames are configurable via the
     * [defaultTemplateExtensions](http://craftcms.com/docs/config-settings#defaultTemplateExtensions) and
     * [indexTemplateFilenames](http://craftcms.com/docs/config-settings#indexTemplateFilenames) config settings.
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
     * The actual directory that those files will depend on the current [[setTemplateMode() template mode]]
     * (probably craft/templates/ if it’s a front-end site request, and craft/app/templates/ if it’s a Control
     * Panel request).
     *
     * If this is a front-end site request, a folder named after the current site handle will be checked first.
     *
     * - craft/templates/SiteHandle/...
     * - craft/templates/...
     *
     * And finaly, if this is a Control Panel request _and_ the template name includes multiple segments _and_ the first
     * segment of the template name matches a plugin’s handle, then Craft will look for a template named with the
     * remaining segments within that plugin’s templates/ subfolder.
     *
     * To put it all together, here’s where Craft would look for a template named “foo/bar”, depending on the type of
     * request it is:
     *
     * - Front-end site requests:
     *
     *     - craft/templates/SiteHandle/foo/bar
     *     - craft/templates/SiteHandle/foo/bar.html
     *     - craft/templates/SiteHandle/foo/bar.twig
     *     - craft/templates/SiteHandle/foo/bar/index.html
     *     - craft/templates/SiteHandle/foo/bar/index.twig
     *     - craft/templates/foo/bar
     *     - craft/templates/foo/bar.html
     *     - craft/templates/foo/bar.twig
     *     - craft/templates/foo/bar/index.html
     *     - craft/templates/foo/bar/index.twig
     *
     * - Control Panel requests:
     *
     *     - craft/app/templates/foo/bar
     *     - craft/app/templates/foo/bar.html
     *     - craft/app/templates/foo/bar.twig
     *     - craft/app/templates/foo/bar/index.html
     *     - craft/app/templates/foo/bar/index.twig
     *     - craft/plugins/foo/templates/bar
     *     - craft/plugins/foo/templates/bar.html
     *     - craft/plugins/foo/templates/bar.twig
     *     - craft/plugins/foo/templates/bar/index.html
     *     - craft/plugins/foo/templates/bar/index.twig
     *
     * @param string $name The name of the template.
     *
     * @return string|false The path to the template if it exists, or `false`.
     */
    public function resolveTemplate($name)
    {
        // Normalize the template name
        $name = trim(preg_replace('#/{2,}#', '/', strtr($name, '\\', '/')), '/');

        $key = $this->_templatesPath.':'.$name;

        // Is this template path already cached?
        if (isset($this->_templatePaths[$key])) {
            return $this->_templatePaths[$key];
        }

        // Validate the template name
        $this->_validateTemplateName($name);

        // Look for the template in the main templates folder
        $basePaths = [];

        // Should we be looking for a localized version of the template?
        $request = Craft::$app->getRequest();
        if (!$request->getIsConsoleRequest() && $request->getIsSiteRequest() && Craft::$app->getIsInstalled()) {
            $sitePath = $this->_templatesPath.'/'.Craft::$app->getSites()->currentSite->handle;
            if (Io::folderExists($sitePath)) {
                $basePaths[] = $sitePath;
            }
        }

        $basePaths[] = $this->_templatesPath;

        foreach ($basePaths as $basePath) {
            if (($path = $this->_resolveTemplate($basePath, $name)) !== null) {
                return $this->_templatePaths[$key] = $path;
            }
        }

        // Otherwise maybe it's a plugin template?

        // Only attempt to match against a plugin's templates if this is a CP or action request.

        if (!$request->getIsConsoleRequest() && ($request->getIsCpRequest() || Craft::$app->getRequest()->getIsActionRequest())) {
            // Sanitize
            $name = StringHelper::convertToUtf8($name);

            $parts = array_filter(explode('/', $name));
            $pluginHandle = StringHelper::toLowerCase(array_shift($parts));

            if ($pluginHandle && ($plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle)) !== null) {
                // Get the template path for the plugin.
                $basePath = Craft::$app->getPath()->getPluginsPath().'/'.StringHelper::toLowerCase($plugin->getHandle()).'/templates';

                // Get the new template name to look for within the plugin's templates folder
                $tempName = implode('/', $parts);

                if (($path = $this->_resolveTemplate($basePath,
                        $tempName)) !== null
                ) {
                    return $this->_templatePaths[$key] = $path;
                }
            }
        }

        return false;
    }

    /**
     * Registers a CSS file from the resources/ folder.
     *
     * @param string $path    The resource path for the CSS file to be registered.
     * @param array  $options The HTML attributes for the link tag. Please refer to [[Html::cssFile()]] for
     *                        the supported options. The following options are specially handled and are not treated as HTML attributes:
     *
     * - `depends`: array, specifies the names of the asset bundles that this CSS file depends on.
     *
     * @param string $key     The key that identifies the CSS script file. If null, it will use
     *                        $url as the key. If two CSS files are registered with the same key, the latter
     *                        will overwrite the former.
     */
    public function registerCssResource($path, $options = [], $key = null)
    {
        $this->_registerResource($path, $options, $key, 'css');
    }

    /**
     * Registers a JS file from the resources/ folder.
     *
     * @param string $path    The resource path for the JS file to be registered.
     * @param array  $options the HTML attributes for the script tag. The following options are specially handled
     *                        and are not treated as HTML attributes:
     *
     * - `depends`: array, specifies the names of the asset bundles that this JS file depends on.
     * - `position`: specifies where the JS script tag should be inserted in a page. The possible values are:
     *     * [[POS_HEAD]]: in the head section
     *     * [[POS_BEGIN]]: at the beginning of the body section
     *     * [[POS_END]]: at the end of the body section. This is the default value.
     *
     * Please refer to [[Html::jsFile()]] for other supported options.
     *
     * @param string $key     the key that identifies the JS script file. If null, it will use
     *                        $url as the key. If two JS files are registered with the same key, the latter
     *                        will overwrite the former.
     */
    public function registerJsResource($path, $options = [], $key = null)
    {
        $this->_registerResource($path, $options, $key, 'js');
    }

    /**
     * Registers a hi-res CSS code block.
     *
     * @param string $css     the CSS code block to be registered
     * @param array  $options the HTML attributes for the style tag.
     * @param string $key     the key that identifies the CSS code block. If null, it will use
     *                        $css as the key. If two CSS code blocks are registered with the same key, the latter
     *                        will overwrite the former.
     */
    public function registerHiResCss($css, $options = [], $key = null)
    {
        $css = "@media only screen and (-webkit-min-device-pixel-ratio: 1.5),\n".
            "only screen and (   -moz-min-device-pixel-ratio: 1.5),\n".
            "only screen and (     -o-min-device-pixel-ratio: 3/2),\n".
            "only screen and (        min-device-pixel-ratio: 1.5),\n".
            "only screen and (        min-resolution: 1.5dppx){\n".
            $css."\n".
            '}';

        $this->registerCss($css, $options, $key);
    }

    /**
     * @inheritdoc
     */
    public function registerJs($js, $position = self::POS_READY, $key = null)
    {
        // Trim any whitespace and ensure it ends with a semicolon.
        $js = StringHelper::ensureRight(trim($js, " \t\n\r\0\x0B;"), ';');
        parent::registerJs($js, $position, $key);
    }

    /**
     * Starts a Javascript buffer.
     *
     * Javascript buffers work similarly to [output buffers](http://php.net/manual/en/intro.outcontrol.php) in PHP.
     * Once you’ve started a Javascript buffer, any Javascript code included with [[registerJs()]] will be included
     * in a buffer, and you will have the opportunity to fetch all of that code via [[clearJsBuffer()]] without
     * having it actually get output to the page.
     *
     * @return void
     */
    public function startJsBuffer()
    {
        // Save any currently queued JS into a new buffer, and reset the active JS queue
        $this->_jsBuffers[] = $this->js;
        $this->js = null;
    }

    /**
     * Clears and ends a Javascript buffer, returning whatever Javascript code was included while the buffer was active.
     *
     * @param boolean $scriptTag Whether the Javascript code should be wrapped in a `<script>` tag. Defaults to `true`.
     *
     * @return string|null|false The JS code that was included in the active JS buffer, or `false` if there isn’t one
     */
    public function clearJsBuffer($scriptTag = true)
    {
        if (empty($this->_jsBuffers)) {
            return false;
        }

        // Combine the JS
        $js = '';

        foreach ([
                     self::POS_HEAD,
                     self::POS_BEGIN,
                     self::POS_END,
                     self::POS_LOAD,
                     self::POS_READY
                 ] as $pos) {
            if (!empty($this->js[$pos])) {
                $js .= implode("\n", $this->js[$pos])."\n";
            }
        }

        // Set the active queue to the last one
        $this->js = array_pop($this->_jsBuffers);

        if ($scriptTag === true && !empty($js)) {
            return Html::script($js, ['type' => 'text/javascript']);
        }

        return $js;
    }

    /**
     * Returns the content to be inserted in the head section.
     *
     * This includes:
     *
     * - Meta tags registered using [[registerMetaTag()]]
     * - Link tags registered with [[registerLinkTag()]]
     * - CSS code registered with [[registerCss()]]
     * - CSS files registered with [[registerCssFile()]]
     * - JS code registered with [[registerJs()]] with the position set to [[POS_HEAD]]
     * - JS files registered with [[registerJsFile()]] with the position set to [[POS_HEAD]]
     *
     * @param boolean $clear Whether the content should be cleared from the queue (default is true)
     *
     * @return string the rendered content
     */
    public function getHeadHtml($clear = true)
    {
        // Register any asset bundles
        $this->registerAllAssetFiles();

        $html = $this->renderHeadHtml();

        if ($clear === true) {
            $this->metaTags = null;
            $this->linkTags = null;
            $this->css = null;
            $this->cssFiles = null;
            unset($this->jsFiles[self::POS_HEAD], $this->js[self::POS_HEAD]);
        }

        return $html;
    }

    /**
     * Returns the content to be inserted at the end of the body section.
     *
     * This includes:
     *
     * - JS code registered with [[registerJs()]] with the position set to [[POS_BEGIN]], [[POS_END]], [[POS_READY]], or [[POS_LOAD]]
     * - JS files registered with [[registerJsFile()]] with the position set to [[POS_BEGIN]] or [[POS_END]]
     *
     * @param boolean $clear Whether the content should be cleared from the queue (default is true)
     *
     * @return string the rendered content
     */
    public function getBodyHtml($clear = true)
    {
        // Register any asset bundles
        $this->registerAllAssetFiles();

        // Get the rendered body begin+end HTML
        $html = $this->renderBodyBeginHtml().
            $this->renderBodyEndHtml(true);

        // Clear out the queued up files
        if ($clear === true) {
            unset(
                $this->jsFiles[self::POS_BEGIN],
                $this->jsFiles[self::POS_END],
                $this->js[self::POS_BEGIN],
                $this->js[self::POS_END],
                $this->js[self::POS_READY],
                $this->js[self::POS_LOAD]
            );
        }

        return $html;
    }

    /**
     * Registers all files provided by all registered asset bundles, including depending bundles files.
     * Removes a bundle from [[assetBundles]] once files are registered.
     *
     * @return void
     */
    protected function registerAllAssetFiles()
    {
        foreach (array_keys($this->assetBundles) as $bundle) {
            $this->registerAssetFiles($bundle);
        }
    }

    /**
     * Prepares translations for inclusion in the template, to be used by the JS.
     *
     * @param string   $category The category the messages are in
     * @param string[] $messages The messages to be translated
     *
     * @return void
     */
    public function registerTranslations($category, $messages)
    {
        foreach ($messages as $message) {
            if (!isset($this->_translations[$category]) || !array_key_exists($message, $this->_translations[$category])) {
                $translation = Craft::t($category, $message);

                if ($translation != $message) {
                    $this->_translations[$category][$message] = $translation;
                } else {
                    $this->_translations[$category][$message] = null;
                }
            }
        }
    }

    /**
     * Returns the translations prepared for inclusion by registerTranslations(), in JSON, and flushes out the
     * translations queue.
     *
     * @return string A JSON-encoded array of source/translation message mappings.
     *
     * @todo Add a $json param that determines whether the returned array should be JSON-encoded (defaults to true).
     */
    public function getTranslations()
    {
        $translations = Json::encode(array_filter(array_map('array_filter', $this->_translations)));
        $this->_translations = [];

        return $translations;
    }

    /**
     * Returns the active namespace.
     *
     * This is the default namespaces that will be used when [[namespaceInputs()]], [[namespaceInputName()]],
     * and [[namespaceInputId()]] are called, if their $namespace arguments are null.
     *
     * @return string The namespace.
     */
    public function getNamespace()
    {
        return $this->_namespace;
    }

    /**
     * Sets the active namespace.
     *
     * This is the default namespaces that will be used when [[namespaceInputs()]], [[namespaceInputName()]],
     * and [[namespaceInputId()]] are called, if their $namespace arguments are null.
     *
     * @param string $namespace The new namespace.
     *
     * @return void
     */
    public function setNamespace($namespace)
    {
        $this->_namespace = $namespace;
    }

    /**
     * Returns the current template mode (either 'site' or 'cp').
     *
     * @return string Either 'site' or 'cp'.
     */
    public function getTemplateMode()
    {
        return $this->_templateMode;
    }

    /**
     * Sets the current template mode.
     *
     * The template mode defines:
     *
     * - the base path that templates should be looked for in
     * - the default template file extensions that should be automatically added when looking for templates
     * - the "index" template filenames that sholud be checked when looking for templates
     *
     * @param string $templateMode Either 'site' or 'cp'
     *
     * @return void
     * @throws Exception if $templateMode is invalid
     */
    public function setTemplateMode($templateMode)
    {
        // Validate
        if (!in_array($templateMode, [
            self::TEMPLATE_MODE_CP,
            self::TEMPLATE_MODE_SITE
        ])
        ) {
            throw new Exception('"'.$templateMode.'" is not a valid template mode');
        }

        // Set the new template mode
        $this->_templateMode = $templateMode;

        // Update everything
        if ($templateMode == self::TEMPLATE_MODE_CP) {
            $this->setTemplatesPath(Craft::$app->getPath()->getCpTemplatesPath());
            $this->_defaultTemplateExtensions = ['html', 'twig'];
            $this->_indexTemplateFilenames = ['index'];
        } else {
            $this->setTemplatesPath(Craft::$app->getPath()->getSiteTemplatesPath());
            $configService = Craft::$app->getConfig();
            $this->_defaultTemplateExtensions = $configService->get('defaultTemplateExtensions');
            $this->_indexTemplateFilenames = $configService->get('indexTemplateFilenames');
        }
    }

    /**
     * Returns the base path that templates should be found in.
     *
     * @return string
     */
    public function getTemplatesPath()
    {
        return $this->_templatesPath;
    }

    /**
     * Sets the base path that templates should be found in.
     *
     * @param string $templatesPath
     *
     * @return void
     */
    public function setTemplatesPath($templatesPath)
    {
        $this->_templatesPath = rtrim($templatesPath, '/\\');
    }

    /**
     * Renames HTML input names so they belong to a namespace.
     *
     * This method will go through the passed-in $html looking for `name=` attributes, and renaming their values such
     * that they will live within the passed-in $namespace (or the [[getNamespace() active namespace]]).
     *
     * By default, any `id=`, `for=`, `list=`, `data-target=`, `data-reverse-target=`, and `data-target-prefix=`
     * attributes will get namespaced as well, by prepending the namespace and a dash to their values.
     *
     * For example, the following HTML:
     *
     * ```markup
     * <label for="title">Title</label>
     * <input type="text" name="title" id="title">
     * ```
     *
     * would become this, if it were namespaced with “foo”:
     *
     * ```markup
     * <label for="foo-title">Title</label>
     * <input type="text" name="foo[title]" id="foo-title">
     * ```
     *
     * Attributes that are already namespaced will get double-namespaced. For example, the following HTML:
     *
     * ```markup
     * <label for="bar-title">Title</label>
     * <input type="text" name="bar[title]" id="title">
     * ```
     *
     * would become:
     *
     * ```markup
     * <label for="foo-bar-title">Title</label>
     * <input type="text" name="foo[bar][title]" id="foo-bar-title">
     * ```
     *
     * @param string  $html            The template with the inputs.
     * @param string  $namespace       The namespace. Defaults to the [[getNamespace() active namespace]].
     * @param boolean $otherAttributes Whether id=, for=, etc., should also be namespaced. Defaults to `true`.
     *
     * @return string The HTML with namespaced input names.
     */
    public function namespaceInputs($html, $namespace = null, $otherAttributes = true)
    {
        if (!is_string($html) || $html === '') {
            return '';
        }

        if ($namespace === null) {
            $namespace = $this->getNamespace();
        }

        if ($namespace) {
            // Protect the textarea content
            $this->_textareaMarkers = [];
            $html = preg_replace_callback('/(<textarea\b[^>]*>)(.*?)(<\/textarea>)/is',
                [$this, '_createTextareaMarker'], $html);

            // name= attributes
            $html = preg_replace('/(?<![\w\-])(name=(\'|"))([^\'"\[\]]+)([^\'"]*)\2/i', '$1'.$namespace.'[$3]$4$2', $html);

            // id= and for= attributes
            if ($otherAttributes) {
                $idNamespace = $this->formatInputId($namespace);
                $html = preg_replace('/(?<![\w\-])((id|for|list|aria\-labelledby|data\-target|data\-reverse\-target|data\-target\-prefix)=(\'|")#?)([^\.\'"][^\'"]*)\3/i', '$1'.$idNamespace.'-$4$3', $html);
            }

            // Bring back the textarea content
            $html = str_replace(array_keys($this->_textareaMarkers), array_values($this->_textareaMarkers), $html);
        }

        return $html;
    }

    /**
     * Namespaces an input name.
     *
     * This method applies the same namespacing treatment that [[namespaceInputs()]] does to `name=` attributes,
     * but only to a single value, which is passed directly into this method.
     *
     * @param string $inputName The input name that should be namespaced.
     * @param string $namespace The namespace. Defaults to the [[getNamespace() active namespace]].
     *
     * @return string The namespaced input name.
     */
    public function namespaceInputName($inputName, $namespace = null)
    {
        if ($namespace === null) {
            $namespace = $this->getNamespace();
        }

        if ($namespace) {
            $inputName = preg_replace('/([^\'"\[\]]+)([^\'"]*)/', $namespace.'[$1]$2', $inputName);
        }

        return $inputName;
    }

    /**
     * Namespaces an input ID.
     *
     * This method applies the same namespacing treatment that [[namespaceInputs()]] does to `id=` attributes,
     * but only to a single value, which is passed directly into this method.
     *
     * @param string $inputId   The input ID that should be namespaced.
     * @param string $namespace The namespace. Defaults to the [[getNamespace() active namespace]].
     *
     * @return string The namespaced input ID.
     */
    public function namespaceInputId($inputId, $namespace = null)
    {
        if ($namespace === null) {
            $namespace = $this->getNamespace();
        }

        if ($namespace) {
            $inputId = $this->formatInputId($namespace).'-'.$inputId;
        }

        return $inputId;
    }

    /**
     * Formats an ID out of an input name.
     *
     * This method takes a given input name and returns a valid ID based on it.
     *
     * For example, if given the following input name:
     *
     *     foo[bar][title]
     *
     * the following ID would be returned:
     *
     *     foo-bar-title
     *
     * @param string $inputName The input name.
     *
     * @return string The input ID.
     */
    public function formatInputId($inputName)
    {
        return rtrim(preg_replace('/[\[\]\\\]+/', '-', $inputName), '-');
    }

    /**
     * Queues up a method to be called by a given template hook.
     *
     * For example, if you place this in your plugin’s [[BasePlugin::init() init()]] method:
     *
     * ```php
     * Craft::$app->getView()->hook('myAwesomeHook', function(&$context)
     * {
     *     $context['foo'] = 'bar';
     *
     *     return 'Hey!';
     * });
     * ```
     *
     * you would then be able to add this to any template:
     *
     * ```twig
     * {% hook "myAwesomeHook" %}
     * ```
     *
     * When the hook tag gets invoked, your template hook function will get called. The $context argument will be the
     * current Twig context array, which you’re free to manipulate. Any changes you make to it will be available to the
     * template following the tag. Whatever your template hook function returns will be output in place of the tag in
     * the template as well.
     *
     * @param string   $hook   The hook name.
     * @param callback $method The callback function.
     *
     * @return void
     */
    public function hook($hook, $method)
    {
        $this->_hooks[$hook][] = $method;
    }

    /**
     * Invokes a template hook.
     *
     * This is called by [[HookNode `{% hook %]]` tags).
     *
     * @param string $hook     The hook name.
     * @param array  &$context The current template context.
     *
     * @return string Whatever the hooks returned.
     */
    public function invokeHook($hook, &$context)
    {
        $return = '';

        if (isset($this->_hooks[$hook])) {
            foreach ($this->_hooks[$hook] as $method) {
                $return .= call_user_func_array($method, [&$context]);
            }
        }

        return $return;
    }

    /**
     * Loads plugin-supplied Twig extensions now that all plugins have been loaded.
     *
     * @param Event $event
     *
     * @return void
     */
    public function onPluginsLoaded(Event $event)
    {
        foreach ($this->_twigs as $twig) {
            $this->_addPluginTwigExtensions($twig);
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Ensures that a template name isn't null, and that it doesn't lead outside the template folder. Borrowed from
     * [[Twig_Loader_Filesystem]].
     *
     * @param string $name
     *
     * @throws \Twig_Error_Loader
     */
    private function _validateTemplateName($name)
    {
        if (StringHelper::contains($name, "\0")) {
            throw new \Twig_Error_Loader(Craft::t('app', 'A template name cannot contain NUL bytes.'));
        }

        if (Path::ensurePathIsContained($name) === false) {
            throw new \Twig_Error_Loader(Craft::t('app', 'Looks like you try to load a template outside the template folder: {template}.', ['template' => $name]));
        }
    }

    /**
     * Searches for a template files, and returns the first match if there is one.
     *
     * @param string $basePath The base path to be looking in.
     * @param string $name     The name of the template to be looking for.
     *
     * @return string|null The matching file path, or `null`.
     */
    private function _resolveTemplate($basePath, $name)
    {
        // Normalize the path and name
        $basePath = rtrim(Io::normalizePathSeparators($basePath), '/\\');
        $name = trim(Io::normalizePathSeparators($name), '/');

        // $name could be an empty string (e.g. to load the homepage template)
        if ($name) {
            // Maybe $name is already the full file path
            $testPath = $basePath.'/'.$name;

            if (Io::fileExists($testPath)) {
                return $testPath;
            }

            foreach ($this->_defaultTemplateExtensions as $extension) {
                $testPath = $basePath.'/'.$name.'.'.$extension;

                if (Io::fileExists($testPath)) {
                    return $testPath;
                }
            }
        }

        foreach ($this->_indexTemplateFilenames as $filename) {
            foreach ($this->_defaultTemplateExtensions as $extension) {
                $testPath = $basePath.'/'.($name ? $name.'/' : '').$filename.'.'.$extension;

                if (Io::fileExists($testPath)) {
                    return $testPath;
                }
            }
        }

        return null;
    }

    /**
     * Returns the Twig environment options
     *
     * @return array
     */
    private function _getTwigOptions()
    {
        if (!isset($this->_twigOptions)) {
            $this->_twigOptions = [
                'base_template_class' => '\\craft\\app\\web\\twig\\Template',
                // See: https://github.com/twigphp/Twig/issues/1951
                'cache' => rtrim(Craft::$app->getPath()->getCompiledTemplatesPath(), '/'),
                'auto_reload' => true,
                'charset' => Craft::$app->charset,
            ];

            if (Craft::$app->getConfig()->get('devMode')) {
                $this->_twigOptions['debug'] = true;
                $this->_twigOptions['strict_variables'] = true;
            }
        }

        return $this->_twigOptions;
    }

    /**
     * Adds any plugin-supplied Twig extensions to a given Twig instance.
     *
     * @param \Twig_Environment $twig
     *
     * @return void
     */
    private function _addPluginTwigExtensions(\Twig_Environment $twig)
    {
        // Check if the Plugins service has been loaded yet
        $pluginsService = Craft::$app->getPlugins();
        $pluginsService->loadPlugins();

        // Could be that this is getting called in the middle of plugin loading, so check again
        if ($pluginsService->arePluginsLoaded()) {
            $pluginExtensions = $pluginsService->call('addTwigExtension');

            try {
                foreach ($pluginExtensions as $extension) {
                    // It's possible for a plugin to register multiple extensions.
                    if (is_array($extension)) {
                        foreach ($extension as $innerExtension) {
                            $twig->addExtension($innerExtension);
                        }
                    } else {
                        $twig->addExtension($extension);
                    }
                }
            } catch (\LogicException $e) {
                Craft::warning('Tried to register plugin-supplied Twig extensions, but Twig environment has already initialized its extensions.', __METHOD__);

                return;
            }
        } else {
            // Wait around for plugins to actually be loaded, then do it for all Twig environments that have been created.
            Event::on(Plugins::class, Plugins::EVENT_AFTER_LOAD_PLUGINS,
                [$this, 'onPluginsLoaded']);
        }
    }

    /**
     * Registers an asset bundle for a file in the resources/ folder.
     *
     * @param string $path
     * @param array  $options
     * @param string $key
     * @param string $kind
     */
    private function _registerResource($path, $options, $key, $kind)
    {
        $key = $key ?: 'resource:'.$path;

        // Make AppAsset the default dependency
        $depends = (array)ArrayHelper::remove($options, 'depends', [
            AppAsset::class
        ]);

        $sourcePath = Craft::getAlias('@app/resources');

        // If the resource doesn't exist in craft/app/resources, check plugins' resources/ subfolders
        if (!Io::fileExists($sourcePath.'/'.$path)) {
            $pathParts = explode('/', $path);

            if (count($pathParts) > 1) {
                $pluginHandle = array_shift($pathParts);
                $pluginSourcePath = Craft::getAlias('@craft/plugins/'.$pluginHandle.'/resources');
                $pluginSubpath = implode('/', $pathParts);

                if (Io::fileExists($pluginSourcePath.'/'.$pluginSubpath)) {
                    $sourcePath = $pluginSourcePath;
                    $path = $pluginSubpath;
                }
            }
        }

        $bundle = new AssetBundle([
            'sourcePath' => $sourcePath,
            "{$kind}" => [$path],
            "{$kind}Options" => $options,
            'depends' => $depends,
        ]);

        $am = $this->getAssetManager();
        $am->bundles[$key] = $bundle;
        $bundle->publish($am);

        $this->registerAssetBundle($key);
    }

    /**
     * Replaces textarea contents with a marker.
     *
     * @param array $matches
     *
     * @return string
     */
    private function _createTextareaMarker($matches)
    {
        $marker = '{marker:'.StringHelper::randomString().'}';
        $this->_textareaMarkers[$marker] = $matches[2];

        return $matches[1].$marker.$matches[3];
    }

    /**
     * Returns the HTML for an element in the CP.
     *
     * @param array &$context
     *
     * @return string|null
     */
    private function _getCpElementHtml(&$context)
    {
        if (!isset($context['element'])) {
            return null;
        }

        /** @var Element $element */
        $element = $context['element'];

        if (!isset($context['context'])) {
            $context['context'] = 'index';
        }

        // How big is the element going to be?
        if (isset($context['size']) && ($context['size'] == 'small' || $context['size'] == 'large')) {
            $elementSize = $context['size'];
        } else if (isset($context['viewMode']) && $context['viewMode'] == 'thumbs') {
            $elementSize = 'large';
        } else {
            $elementSize = 'small';
        }

        // Create the thumb/icon image, if there is one
        // ---------------------------------------------------------------------

        $thumbUrl = $element->getThumbUrl(self::$_elementThumbSizes[0]);

        if ($thumbUrl) {
            $srcsets = [];

            foreach (self::$_elementThumbSizes as $i => $size) {
                if ($i == 0) {
                    $srcset = $thumbUrl;
                } else {
                    $srcset = $element->getThumbUrl($size);
                }

                $srcsets[] = $srcset.' '.$size.'w';
            }

            $imgHtml = '<div class="elementthumb">'.
                '<img '.
                'sizes="'.($elementSize == 'small' ? self::$_elementThumbSizes[0] : self::$_elementThumbSizes[2]).'px" '.
                'srcset="'.implode(', ', $srcsets).'" '.
                'alt="">'.
                '</div> ';
        } else {
            $imgHtml = '';
        }

        $html = '<div class="element '.$elementSize;

        if ($context['context'] == 'field') {
            $html .= ' removable';
        }

        if ($element::hasStatuses()) {
            $html .= ' hasstatus';
        }

        if ($thumbUrl) {
            $html .= ' hasthumb';
        }

        $label = HtmlHelper::encode($element);

        $html .= '" data-id="'.$element->id.'" data-site-id="'.$element->siteId.'" data-status="'.$element->getStatus().'" data-label="'.$label.'" data-url="'.$element->getUrl().'"';

        if ($element->level) {
            $html .= ' data-level="'.$element->level.'"';
        }

        $isEditable = ElementHelper::isElementEditable($element);

        if ($isEditable) {
            $html .= ' data-editable';
        }

        $html .= '>';

        if ($context['context'] == 'field' && isset($context['name'])) {
            $html .= '<input type="hidden" name="'.$context['name'].'[]" value="'.$element->id.'">';
            $html .= '<a class="delete icon" title="'.Craft::t('app',
                    'Remove').'"></a> ';
        }

        if ($element::hasStatuses()) {
            $html .= '<span class="status '.$context['element']->getStatus().'"></span>';
        }

        $html .= $imgHtml;
        $html .= '<div class="label">';

        $html .= '<span class="title">';

        if ($context['context'] == 'index' && ($cpEditUrl = $element->getCpEditUrl())) {
            $cpEditUrl = HtmlHelper::encode($cpEditUrl);
            $html .= "<a href=\"{$cpEditUrl}\">{$label}</a>";
        } else {
            $html .= $label;
        }

        $html .= '</span></div></div>';

        return $html;
    }
}
