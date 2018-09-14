<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\base\Element;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\TemplateEvent;
use craft\helpers\ElementHelper;
use craft\helpers\FileHelper;
use craft\helpers\Html as HtmlHelper;
use craft\helpers\Json;
use craft\helpers\Path;
use craft\helpers\StringHelper;
use craft\web\twig\Environment;
use craft\web\twig\Extension;
use craft\web\twig\Template;
use craft\web\twig\TemplateLoader;
use Twig_ExtensionInterface;
use yii\base\Arrayable;
use yii\base\Exception;
use yii\base\Model;
use yii\helpers\Html;
use yii\web\AssetBundle as YiiAssetBundle;

/**
 * @inheritdoc
 * @property string $templateMode the current template mode (either `site` or `cp`)
 * @property string $templatesPath the base path that templates should be found in
 * @property string|null $namespace the active namespace
 * @property-read array $cpTemplateRoots any registered CP template roots
 * @property-read array $siteTemplateRoots any registered site template roots
 * @property-read bool $isRenderingPageTemplate whether a page template is currently being rendered
 * @property-read bool $isRenderingTemplate whether a template is currently being rendered
 * @property-read Environment $twig the Twig environment
 * @property-read string $bodyHtml the content to be inserted at the end of the body section
 * @property-read string $headHtml the content to be inserted in the head section
 * @property-write string[] $registeredAssetBundles the asset bundle names that should be marked as already registered
 * @property-write string[] $registeredJsFiles the JS files that should be marked as already registered
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class View extends \yii\web\View
{
    // Constants
    // =========================================================================

    /**
     * @event RegisterTemplateRootsEvent The event that is triggered when registering CP template roots
     */
    const EVENT_REGISTER_CP_TEMPLATE_ROOTS = 'registerCpTemplateRoots';

    /**
     * @event RegisterTemplateRootsEvent The event that is triggered when registering site template roots
     */
    const EVENT_REGISTER_SITE_TEMPLATE_ROOTS = 'registerSiteTemplateRoots';

    /**
     * @event TemplateEvent The event that is triggered before a template gets rendered
     */
    const EVENT_BEFORE_RENDER_TEMPLATE = 'beforeRenderTemplate';

    /**
     * @event TemplateEvent The event that is triggered after a template gets rendered
     */
    const EVENT_AFTER_RENDER_TEMPLATE = 'afterRenderTemplate';

    /**
     * @event TemplateEvent The event that is triggered before a page template gets rendered
     */
    const EVENT_BEFORE_RENDER_PAGE_TEMPLATE = 'beforeRenderPageTemplate';

    /**
     * @event TemplateEvent The event that is triggered after a page template gets rendered
     */
    const EVENT_AFTER_RENDER_PAGE_TEMPLATE = 'afterRenderPageTemplate';

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
     * @var Environment|null The Twig environment instance used for CP templates
     */
    private $_cpTwig;

    /**
     * @var Environment|null The Twig environment instance used for site templates
     */
    private $_siteTwig;

    /**
     * @var
     */
    private $_twigOptions;

    /**
     * @var Twig_ExtensionInterface[] List of Twig extensions registered with [[registerTwigExtension()]]
     */
    private $_twigExtensions = [];

    /**
     * @var
     */
    private $_templatePaths;

    /**
     * @var
     */
    private $_objectTemplates;

    /**
     * @var string|null
     */
    private $_templateMode;

    /**
     * @var array|null
     */
    private $_cpTemplateRoots;

    /**
     * @var array|null
     */
    private $_siteTemplateRoots;

    /**
     * @var array|null
     */
    private $_templateRoots;

    /**
     * @var string|null The root path to look for templates in
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
     * @var array the registered generic `<script>` code blocks
     * @see registerScript()
     */
    private $_scripts;

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

    /**
     * @var string[]
     * @see registerAssetFiles()
     * @see setRegisteredAssetBundles()
     */
    private $_registeredAssetBundles = [];

    /**
     * @var string[]
     * @see registerJsFile()
     * @see setRegisteredJsfiles()
     */
    private $_registeredJsFiles = [];

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
        if ($request->getIsConsoleRequest() || $request->getIsCpRequest()) {
            $this->setTemplateMode(self::TEMPLATE_MODE_CP);
        } else {
            $this->setTemplateMode(self::TEMPLATE_MODE_SITE);
        }

        // Register the cp.elements.element hook
        $this->hook('cp.elements.element', [$this, '_getCpElementHtml']);
    }

    /**
     * Returns the Twig environment.
     *
     * @return Environment
     */
    public function getTwig(): Environment
    {
        return $this->_templateMode === self::TEMPLATE_MODE_CP
            ? $this->_cpTwig ?? ($this->_cpTwig = $this->createTwig())
            : $this->_siteTwig ?? ($this->_siteTwig = $this->createTwig());
    }

    /**
     * Creates a new Twig environment.
     *
     * @return Environment
     */
    public function createTwig(): Environment
    {
        $twig = new Environment(new TemplateLoader($this), $this->_getTwigOptions());

        $twig->addExtension(new \Twig_Extension_StringLoader());
        $twig->addExtension(new Extension($this, $twig));

        if (YII_DEBUG) {
            $twig->addExtension(new \Twig_Extension_Debug());
        }

        // Add plugin-supplied extensions
        foreach ($this->_twigExtensions as $extension) {
            $twig->addExtension($extension);
        }

        // Set our timezone
        /** @var \Twig_Extension_Core $core */
        $core = $twig->getExtension(\Twig_Extension_Core::class);
        $core->setTimezone(Craft::$app->getTimeZone());

        return $twig;
    }

    /**
     * Registers a new Twig extension, which will be added on existing environments and queued up for future environments.
     *
     * @param Twig_ExtensionInterface $extension
     */
    public function registerTwigExtension(Twig_ExtensionInterface $extension)
    {
        $this->_twigExtensions[] = $extension;

        // Add it to any existing Twig environments
        if ($this->_cpTwig !== null) {
            $this->_cpTwig->addExtension($extension);
        }
        if ($this->_siteTwig !== null) {
            $this->_siteTwig->addExtension($extension);
        }
    }

    /**
     * Returns whether a template is currently being rendered.
     *
     * @return bool Whether a template is currently being rendered.
     */
    public function getIsRenderingTemplate(): bool
    {
        return $this->_renderingTemplate !== null;
    }

    /**
     * Renders a Twig template.
     *
     * @param string $template The name of the template to load
     * @param array $variables The variables that should be available to the template
     * @return string the rendering result
     * @throws \Twig_Error_Loader if the template doesn’t exist
     * @throws Exception in case of failure
     * @throws \RuntimeException in case of failure
     */
    public function renderTemplate(string $template, array $variables = []): string
    {
        if (!$this->beforeRenderTemplate($template, $variables)) {
            return '';
        }

        Craft::debug("Rendering template: $template", __METHOD__);

        // Render and return
        $renderingTemplate = $this->_renderingTemplate;
        $this->_renderingTemplate = $template;
        Craft::beginProfile($template, __METHOD__);

        try {
            $output = $this->getTwig()->render($template, $variables);
        } catch (\RuntimeException $e) {
            if (!YII_DEBUG) {
                // Throw a generic exception instead
                throw new Exception('An error occurred when rendering a template.', 0, $e);
            }
            throw $e;
        }

        Craft::endProfile($template, __METHOD__);
        $this->_renderingTemplate = $renderingTemplate;

        $this->afterRenderTemplate($template, $variables, $output);

        return $output;
    }

    /**
     * Returns whether a page template is currently being rendered.
     *
     * @return bool Whether a page template is currently being rendered.
     */
    public function getIsRenderingPageTemplate(): bool
    {
        return $this->_isRenderingPageTemplate;
    }

    /**
     * Renders a Twig template that represents an entire web page.
     *
     * @param string $template The name of the template to load
     * @param array $variables The variables that should be available to the template
     * @return string the rendering result
     */
    public function renderPageTemplate(string $template, array $variables = []): string
    {
        if (!$this->beforeRenderPageTemplate($template, $variables)) {
            return '';
        }

        ob_start();
        ob_implicit_flush(false);

        $isRenderingPageTemplate = $this->_isRenderingPageTemplate;
        $this->_isRenderingPageTemplate = true;

        $this->beginPage();
        echo $this->renderTemplate($template, $variables);
        $this->endPage();

        $this->_isRenderingPageTemplate = $isRenderingPageTemplate;

        $output = ob_get_clean();

        $this->afterRenderPageTemplate($template, $variables, $output);

        return $output;
    }

    /**
     * Renders a macro within a given Twig template.
     *
     * @param string $template The name of the template the macro lives in.
     * @param string $macro The name of the macro.
     * @param array $args Any arguments that should be passed to the macro.
     * @return string The rendered macro output.
     * @throws Exception in case of failure
     * @throws \RuntimeException in case of failure
     */
    public function renderTemplateMacro(string $template, string $macro, array $args = []): string
    {
        $twig = $this->getTwig();
        $twigTemplate = $twig->loadTemplate($template);

        $renderingTemplate = $this->_renderingTemplate;
        $this->_renderingTemplate = $template;

        try {
            $output = call_user_func_array([$twigTemplate, 'macro_' . $macro], $args);
        } catch (\RuntimeException $e) {
            if (!YII_DEBUG) {
                // Throw a generic exception instead
                throw new Exception('An error occurred when rendering a template.', 0, $e);
            }
            throw $e;
        }

        $this->_renderingTemplate = $renderingTemplate;

        return (string)$output;
    }

    /**
     * Renders a template defined in a string.
     *
     * @param string $template The source template string.
     * @param array $variables Any variables that should be available to the template.
     * @return string The rendered template.
     */
    public function renderString(string $template, array $variables = []): string
    {
        $twig = $this->getTwig();
        $twig->setDefaultEscaperStrategy(false);
        $lastRenderingTemplate = $this->_renderingTemplate;
        $this->_renderingTemplate = 'string:' . $template;
        $result = $twig->createTemplate($template)->render($variables);
        $this->_renderingTemplate = $lastRenderingTemplate;
        $twig->setDefaultEscaperStrategy();
        return $result;
    }

    /**
     * Renders an object template.
     *
     * The passed-in `$object` will be available to the template as an `object` variable.
     *
     * The template will be parsed for “property tags” (e.g. `{foo}`), which will get replaced with
     * full Twig output tags (e.g. `{{ object.foo|raw }}`.
     *
     * If `$object` is an instance of [[Arrayable]], any attributes returned by its [[Arrayable::fields()|fields()]] or
     * [[Arrayable::extraFields()|extraFields()]] methods will also be available as variables to the template.
     *
     * @param string $template the source template string
     * @param mixed $object the object that should be passed into the template
     * @param array $variables any additional variables that should be available to the template
     * @return string The rendered template.
     * @throws Exception in case of failure
     * @throws \Throwable in case of failure
     */
    public function renderObjectTemplate(string $template, $object, array $variables = []): string
    {
        // If there are no dynamic tags, just return the template
        if (strpos($template, '{') === false) {
            return $template;
        }

        $twig = $this->getTwig();

        // Temporarily disable strict variables if it's enabled
        $strictVariables = $twig->isStrictVariables();

        if ($strictVariables) {
            $twig->disableStrictVariables();
        }

        // Is this the first time we've parsed this template?
        $cacheKey = md5($template);
        if (!isset($this->_objectTemplates[$cacheKey])) {
            // Replace shortcut "{var}"s with "{{object.var}}"s, without affecting normal Twig tags
            $template = $this->normalizeObjectTemplate($template);
            $this->_objectTemplates[$cacheKey] = $twig->createTemplate($template);
        }

        // Get the variables to pass to the template
        if ($object instanceof Model) {
            foreach ($object->attributes() as $name) {
                if (!isset($variables[$name]) && strpos($template, $name) !== false) {
                    $variables[$name] = $object->$name;
                }
            }
        }

        if ($object instanceof Arrayable) {
            // See if we should be including any of the extra fields
            $extra = [];
            foreach ($object->extraFields() as $field => $definition) {
                if (is_int($field)) {
                    $field = $definition;
                }
                if (strpos($template, $field) !== false) {
                    $extra[] = $field;
                }
            }
            $variables = array_merge($object->toArray([], $extra, false), $variables);
        }

        $variables['object'] = $object;
        $variables['_variables'] = $variables;

        // Render it!
        $twig->setDefaultEscaperStrategy(false);
        $lastRenderingTemplate = $this->_renderingTemplate;
        $this->_renderingTemplate = 'string:' . $template;
        /** @var Template $templateObj */
        $templateObj = $this->_objectTemplates[$cacheKey];

        $e = null;
        try {
            $output = $templateObj->render($variables);
        } catch (\Throwable $e) {
        }

        $this->_renderingTemplate = $lastRenderingTemplate;
        $twig->setDefaultEscaperStrategy();

        // Re-enable strict variables
        if ($strictVariables) {
            $twig->enableStrictVariables();
        }

        if ($e !== null) {
            if (!YII_DEBUG) {
                // Throw a generic exception instead
                throw new Exception('An error occurred when rendering a template.', 0, $e);
            }
            throw $e;
        }

        return $output;
    }

    /**
     * Normalizes an object template for [[renderObjectTemplate()]].
     *
     * @param string $template
     * @return string
     */
    public function normalizeObjectTemplate(string $template): string
    {
        // Tokenize objects (call preg_replace_callback() multiple times in case there are nested objects)
        $tokens = [];
        while (true) {
            $template = preg_replace_callback('/\{\s*([\'"]?)\w+\1\s*:[^\{]+?\}/', function(array $matches) use (&$tokens) {
                $token = 'tok_' . StringHelper::randomString(10);
                $tokens[$token] = $matches[0];
                return $token;
            }, $template, -1, $count);
            if ($count === 0) {
                break;
            }
        }

        // Swap out the remaining {xyz} tags with {{object.xyz}}
        $template = preg_replace('/(?<!\{)\{\s*(\w+)([^\{]*?)\}/', '{{ (_variables.$1 ?? object.$1)$2|raw }}', $template);

        // Bring the objects back
        foreach (array_reverse($tokens) as $token => $value) {
            $template = str_replace($token, $value, $template);
        }

        return $template;
    }

    /**
     * Returns whether a template exists.
     *
     * Internally, this will just call [[resolveTemplate()]] with the given template name, and return whether that
     * method found anything.
     *
     * @param string $name The name of the template.
     * @return bool Whether the template exists.
     */
    public function doesTemplateExist(string $name): bool
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
     * [[\craft\config\GeneralConfig::defaultTemplateExtensions|defaultTemplateExtensions]] and
     * [[\craft\config\GeneralConfig::indexTemplateFilenames|indexTemplateFilenames]] config settings.
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
     * (probably `templates/` if it’s a front-end site request, and `vendor/craftcms/cms/src/templates/` if it’s a Control
     * Panel request).
     *
     * If this is a front-end site request, a folder named after the current site handle will be checked first.
     *
     * - templates/SiteHandle/...
     * - templates/...
     *
     * And finally, if this is a Control Panel request _and_ the template name includes multiple segments _and_ the first
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
     * - Control Panel requests:
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
     * @param string $name The name of the template.
     * @return string|false The path to the template if it exists, or `false`.
     */
    public function resolveTemplate(string $name)
    {
        // Normalize the template name
        $name = trim(preg_replace('#/{2,}#', '/', str_replace('\\', '/', StringHelper::convertToUtf8($name))), '/');

        $key = $this->_templatesPath . ':' . $name;

        // Is this template path already cached?
        if (isset($this->_templatePaths[$key])) {
            return $this->_templatePaths[$key];
        }

        // Validate the template name
        $this->_validateTemplateName($name);

        // Look for the template in the main templates folder
        $basePaths = [];

        // Should we be looking for a localized version of the template?
        if ($this->_templateMode === self::TEMPLATE_MODE_SITE && Craft::$app->getIsInstalled()) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $sitePath = $this->_templatesPath . DIRECTORY_SEPARATOR . Craft::$app->getSites()->getCurrentSite()->handle;
            if (is_dir($sitePath)) {
                $basePaths[] = $sitePath;
            }
        }

        $basePaths[] = $this->_templatesPath;

        foreach ($basePaths as $basePath) {
            if (($path = $this->_resolveTemplate($basePath, $name)) !== null) {
                return $this->_templatePaths[$key] = $path;
            }
        }

        unset($basePaths);

        // Check any registered template roots
        if ($this->_templateMode === self::TEMPLATE_MODE_CP) {
            $roots = $this->getCpTemplateRoots();
        } else {
            $roots = $this->getSiteTemplateRoots();
        }

        if (!empty($roots)) {
            foreach ($roots as $templateRoot => $basePaths) {
                /** @var string[] $basePaths */
                $templateRootLen = strlen($templateRoot);
                if (strncasecmp($templateRoot . '/', $name . '/', $templateRootLen + 1) === 0) {
                    $subName = strlen($name) === $templateRootLen ? '' : substr($name, $templateRootLen + 1);
                    foreach ($basePaths as $basePath) {
                        if (($path = $this->_resolveTemplate($basePath, $subName)) !== null) {
                            return $this->_templatePaths[$key] = $path;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Returns any registered CP template roots.
     *
     * @return array
     */
    public function getCpTemplateRoots(): array
    {
        return $this->_getTemplateRoots('cp');
    }

    /**
     * Returns any registered site template roots.
     *
     * @return array
     */
    public function getSiteTemplateRoots(): array
    {
        return $this->_getTemplateRoots('site');
    }

    /**
     * Registers a hi-res CSS code block.
     *
     * @param string $css the CSS code block to be registered
     * @param array $options the HTML attributes for the style tag.
     * @param string|null $key the key that identifies the CSS code block. If null, it will use
     * $css as the key. If two CSS code blocks are registered with the same key, the latter
     * will overwrite the former.
     * @deprecated in 3.0. Use [[registerCss()]] and type your own media selector.
     */
    public function registerHiResCss(string $css, array $options = [], string $key = null)
    {
        Craft::$app->getDeprecator()->log('registerHiResCss', 'craft\\web\\View::registerHiResCss() has been deprecated. Use registerCss() instead, and type your own media selector.');

        $css = "@media only screen and (-webkit-min-device-pixel-ratio: 1.5),\n" .
            "only screen and (   -moz-min-device-pixel-ratio: 1.5),\n" .
            "only screen and (     -o-min-device-pixel-ratio: 3/2),\n" .
            "only screen and (        min-device-pixel-ratio: 1.5),\n" .
            "only screen and (        min-resolution: 1.5dppx){\n" .
            $css . "\n" .
            '}';

        $this->registerCss($css, $options, $key);
    }

    /**
     * @inheritdoc
     */
    public function registerJs($js, $position = self::POS_READY, $key = null)
    {
        // Trim any whitespace and ensure it ends with a semicolon.
        $js = StringHelper::ensureRight(trim($js, " \t\n\r\0\x0B"), ';');
        parent::registerJs($js, $position, $key);
    }

    /**
     * Starts a JavaScript buffer.
     *
     * JavaScript buffers work similarly to [output buffers](http://php.net/manual/en/intro.outcontrol.php) in PHP.
     * Once you’ve started a JavaScript buffer, any JavaScript code included with [[registerJs()]] will be included
     * in a buffer, and you will have the opportunity to fetch all of that code via [[clearJsBuffer()]] without
     * having it actually get output to the page.
     */
    public function startJsBuffer()
    {
        // Save any currently queued JS into a new buffer, and reset the active JS queue
        $this->_jsBuffers[] = $this->js;
        $this->js = [];
    }

    /**
     * Clears and ends a JavaScript buffer, returning whatever JavaScript code was included while the buffer was active.
     *
     * @param bool $scriptTag Whether the JavaScript code should be wrapped in a `<script>` tag. Defaults to `true`.
     * @return string|false The JS code that was included in the active JS buffer, or `false` if there isn’t one
     */
    public function clearJsBuffer(bool $scriptTag = true)
    {
        if (empty($this->_jsBuffers)) {
            return false;
        }

        // Combine the JS
        $js = '';

        foreach ([self::POS_HEAD, self::POS_BEGIN, self::POS_END, self::POS_LOAD, self::POS_READY] as $pos) {
            if (!empty($this->js[$pos])) {
                $js .= implode("\n", $this->js[$pos]) . "\n";
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
     * @inheritdoc
     */
    public function registerJsFile($url, $options = [], $key = null)
    {
        // If 'depends' is specified, ignore it  for now because the file will
        // get registered as an asset bundle
        if (empty($options['depends'])) {
            $key = $key ?: $url;
            if (isset($this->_registeredJsFiles[$key])) {
                return;
            }
            $this->_registeredJsFiles[$key] = true;
        }

        parent::registerJsFile($url, $options, $key);
    }

    /**
     * Registers a generic `<script>` code block.
     *
     * @param string $script the generic `<script>` code block to be registered
     * @param int $position the position at which the generic `<script>` code block should be inserted
     * in a page. The possible values are:
     * - [[POS_HEAD]]: in the head section
     * - [[POS_BEGIN]]: at the beginning of the body section
     * - [[POS_END]]: at the end of the body section
     * @param array $options the HTML attributes for the `<script>` tag.
     * @param string $key the key that identifies the generic `<script>` code block. If null, it will use
     * $script as the key. If two generic `<script>` code blocks are registered with the same key, the latter
     * will overwrite the former.
     */
    public function registerScript($script, $position = self::POS_END, $options = [], $key = null)
    {
        $key = $key ?: md5($script);
        $this->_scripts[$position][$key] = Html::script($script, $options);
    }

    /**
     * @inheritdoc
     */
    public function endBody()
    {
        $this->registerAssetFlashes();
        parent::endBody();
    }

    /**
     * Returns the content to be inserted in the head section.
     *
     * This includes:
     * - Meta tags registered using [[registerMetaTag()]]
     * - Link tags registered with [[registerLinkTag()]]
     * - CSS code registered with [[registerCss()]]
     * - CSS files registered with [[registerCssFile()]]
     * - JS code registered with [[registerJs()]] with the position set to [[POS_HEAD]]
     * - JS files registered with [[registerJsFile()]] with the position set to [[POS_HEAD]]
     *
     * @param bool $clear Whether the content should be cleared from the queue (default is true)
     * @return string the rendered content
     */
    public function getHeadHtml(bool $clear = true): string
    {
        // Register any asset bundles
        $this->registerAllAssetFiles();

        $html = $this->renderHeadHtml();

        if ($clear === true) {
            $this->metaTags = [];
            $this->linkTags = [];
            $this->css = [];
            $this->cssFiles = [];
            unset($this->jsFiles[self::POS_HEAD], $this->js[self::POS_HEAD]);
        }

        return $html;
    }

    /**
     * Returns the content to be inserted at the end of the body section.
     *
     * This includes:
     * - JS code registered with [[registerJs()]] with the position set to [[POS_BEGIN]], [[POS_END]], [[POS_READY]], or [[POS_LOAD]]
     * - JS files registered with [[registerJsFile()]] with the position set to [[POS_BEGIN]] or [[POS_END]]
     *
     * @param bool $clear Whether the content should be cleared from the queue (default is true)
     * @return string the rendered content
     */
    public function getBodyHtml(bool $clear = true): string
    {
        // Register any asset bundles
        $this->registerAllAssetFiles();

        // Get the rendered body begin+end HTML
        $html = $this->renderBodyBeginHtml() .
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
     * Translates messages for a given translation category, so they will be
     * available for `Craft.t()` calls in the Control Panel.
     * Note this should always be called *before* any JavaScript is registered
     * that will need to use the translations, unless the JavaScript is
     * registered at [[self::POS_READY]].
     *
     * @param string $category The category the messages are in
     * @param string[] $messages The messages to be translated
     */
    public function registerTranslations(string $category, array $messages)
    {
        $jsCategory = Json::encode($category);
        $js = '';

        foreach ($messages as $message) {
            $translation = Craft::t($category, $message);
            if ($translation !== $message) {
                $jsMessage = Json::encode($message);
                $jsTranslation = Json::encode($translation);
                $js .= ($js !== '' ? "\n" : '') . "Craft.translations[{$jsCategory}][{$jsMessage}] = {$jsTranslation};";
            }
        }

        if ($js === '') {
            return;
        }

        $js = <<<JS
if (typeof Craft.translations[{$jsCategory}] === 'undefined') {
    Craft.translations[{$jsCategory}] = {};
}
{$js}
JS;

        $this->registerJs($js, self::POS_BEGIN);
    }

    /**
     * Returns the active namespace.
     *
     * This is the default namespaces that will be used when [[namespaceInputs()]], [[namespaceInputName()]],
     * and [[namespaceInputId()]] are called, if their $namespace arguments are null.
     *
     * @return string|null The namespace.
     */
    public function getNamespace()
    {
        return $this->_namespace;
    }

    /**
     * Sets the active namespace.
     *
     * This is the default namespaces that will be used when [[namespaceInputs()]], [[namespaceInputName()]],
     * and [[namespaceInputId()]] are called, if their|null $namespace arguments are null.
     *
     * @param string|null $namespace The new namespace. Set to null to remove the namespace.
     */
    public function setNamespace(string $namespace = null)
    {
        $this->_namespace = $namespace;
    }

    /**
     * Returns the current template mode (either `site` or `cp`).
     *
     * @return string Either `site` or `cp`.
     */
    public function getTemplateMode(): string
    {
        return $this->_templateMode;
    }

    /**
     * Sets the current template mode.
     *
     * The template mode defines:
     * - the base path that templates should be looked for in
     * - the default template file extensions that should be automatically added when looking for templates
     * - the "index" template filenames that sholud be checked when looking for templates
     *
     * @param string $templateMode Either 'site' or 'cp'
     * @throws Exception if $templateMode is invalid
     */
    public function setTemplateMode(string $templateMode)
    {
        // Validate
        if (!in_array($templateMode, [
            self::TEMPLATE_MODE_CP,
            self::TEMPLATE_MODE_SITE
        ], true)
        ) {
            throw new Exception('"' . $templateMode . '" is not a valid template mode');
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
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $this->_defaultTemplateExtensions = $generalConfig->defaultTemplateExtensions;
            $this->_indexTemplateFilenames = $generalConfig->indexTemplateFilenames;
        }
    }

    /**
     * Returns the base path that templates should be found in.
     *
     * @return string
     */
    public function getTemplatesPath(): string
    {
        return $this->_templatesPath;
    }

    /**
     * Sets the base path that templates should be found in.
     *
     * @param string $templatesPath
     */
    public function setTemplatesPath(string $templatesPath)
    {
        $this->_templatesPath = rtrim($templatesPath, '/\\');
    }

    /**
     * Renames HTML input names so they belong to a namespace.
     *
     * This method will go through the passed-in $html looking for `name=` attributes, and renaming their values such
     * that they will live within the passed-in $namespace (or the [[getNamespace()|active namespace]]).
     * By default, any `id=`, `for=`, `list=`, `data-target=`, `data-reverse-target=`, and `data-target-prefix=`
     * attributes will get namespaced as well, by prepending the namespace and a dash to their values.
     * For example, the following HTML:
     *
     * ```html
     * <label for="title">Title</label>
     * <input type="text" name="title" id="title">
     * ```
     *
     * would become this, if it were namespaced with “foo”:
     *
     * ```html
     * <label for="foo-title">Title</label>
     * <input type="text" name="foo[title]" id="foo-title">
     * ```
     *
     * Attributes that are already namespaced will get double-namespaced. For example, the following HTML:
     *
     * ```html
     * <label for="bar-title">Title</label>
     * <input type="text" name="bar[title]" id="title">
     * ```
     *
     * would become:
     *
     * ```html
     * <label for="foo-bar-title">Title</label>
     * <input type="text" name="foo[bar][title]" id="foo-bar-title">
     * ```
     *
     * @param string $html The template with the inputs.
     * @param string|null $namespace The namespace. Defaults to the [[getNamespace()|active namespace]].
     * @param bool $otherAttributes Whether id=, for=, etc., should also be namespaced. Defaults to `true`.
     * @return string The HTML with namespaced input names.
     */
    public function namespaceInputs(string $html, string $namespace = null, bool $otherAttributes = true): string
    {
        if ($html === '') {
            return '';
        }

        if ($namespace === null) {
            $namespace = $this->getNamespace();
        }

        if ($namespace !== null) {
            // Protect the textarea content
            $this->_textareaMarkers = [];
            $html = preg_replace_callback('/(<textarea\b[^>]*>)(.*?)(<\/textarea>)/is',
                [$this, '_createTextareaMarker'], $html);

            // name= attributes
            $html = preg_replace('/(?<![\w\-])(name=(\'|"))([^\'"\[\]]+)([^\'"]*)\2/i', '$1' . $namespace . '[$3]$4$2', $html);

            // id= and for= attributes
            if ($otherAttributes) {
                $idNamespace = $this->formatInputId($namespace);
                $html = preg_replace('/(?<![\w\-])((id|for|list|aria\-labelledby|data\-target|data\-reverse\-target|data\-target\-prefix)=(\'|")#?)([^\.\'"][^\'"]*)?\3/i', '$1' . $idNamespace . '-$4$3', $html);
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
     * @param string|null $namespace The namespace. Defaults to the [[getNamespace()|active namespace]].
     * @return string The namespaced input name.
     */
    public function namespaceInputName(string $inputName, string $namespace = null): string
    {
        if ($namespace === null) {
            $namespace = $this->getNamespace();
        }

        if ($namespace !== null) {
            $inputName = preg_replace('/([^\'"\[\]]+)([^\'"]*)/', $namespace . '[$1]$2', $inputName);
        }

        return $inputName;
    }

    /**
     * Namespaces an input ID.
     *
     * This method applies the same namespacing treatment that [[namespaceInputs()]] does to `id=` attributes,
     * but only to a single value, which is passed directly into this method.
     *
     * @param string $inputId The input ID that should be namespaced.
     * @param string|null $namespace The namespace. Defaults to the [[getNamespace()|active namespace]].
     * @return string The namespaced input ID.
     */
    public function namespaceInputId(string $inputId, string $namespace = null): string
    {
        if ($namespace === null) {
            $namespace = $this->getNamespace();
        }

        if ($namespace !== null) {
            $inputId = $this->formatInputId($namespace) . '-' . $inputId;
        }

        return $inputId;
    }

    /**
     * Formats an ID out of an input name.
     *
     * This method takes a given input name and returns a valid ID based on it.
     * For example, if given the following input name:
     *     foo[bar][title]
     * the following ID would be returned:
     *     foo-bar-title
     *
     * @param string $inputName The input name.
     * @return string The input ID.
     */
    public function formatInputId(string $inputName): string
    {
        return rtrim(preg_replace('/[\[\]\\\]+/', '-', $inputName), '-');
    }

    /**
     * Queues up a method to be called by a given template hook.
     *
     * For example, if you place this in your plugin’s [[BasePlugin::init()|init()]] method:
     *
     * ```php
     * Craft::$app->view->hook('myAwesomeHook', function(&$context) {
     *     $context['foo'] = 'bar';
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
     * @param string $hook The hook name.
     * @param callback $method The callback function.
     */
    public function hook(string $hook, $method)
    {
        $this->_hooks[$hook][] = $method;
    }

    /**
     * Invokes a template hook.
     *
     * This is called by [[HookNode|`{% hook %}` tags]].
     *
     * @param string $hook The hook name.
     * @param array &$context The current template context.
     * @return string Whatever the hooks returned.
     */
    public function invokeHook(string $hook, array &$context): string
    {
        $return = '';

        if (isset($this->_hooks[$hook])) {
            foreach ($this->_hooks[$hook] as $method) {
                $return .= $method($context);
            }
        }

        return $return;
    }

    /**
     * Sets the JS files that should be marked as already registered.
     *
     * @param string[] $keys
     */
    public function setRegisteredJsFiles(array $keys)
    {
        $this->_registeredJsFiles = array_flip($keys);
    }

    /**
     * Sets the asset bundle names that should be marked as already registered.
     *
     * @param string[] $names Asset bundle names
     */
    public function setRegisteredAssetBundles(array $names)
    {
        $this->_registeredAssetBundles = array_flip($names);
    }

    /**
     * @inheritdoc
     */
    public function endPage($ajaxMode = false)
    {
        if (!$ajaxMode && Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registeredJs('registeredJsFiles', $this->_registeredJsFiles);
            $this->_registeredJs('registeredAssetBundles', $this->_registeredAssetBundles);
        }

        parent::endPage($ajaxMode);
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * Performs actions before a template is rendered.
     *
     * @param mixed $template The name of the template to render
     * @param array &$variables The variables that should be available to the template
     * @return bool Whether the template should be rendered
     */
    public function beforeRenderTemplate(string $template, array &$variables): bool
    {
        // Fire a 'beforeRenderTemplate' event
        $event = new TemplateEvent([
            'template' => $template,
            'variables' => $variables,
        ]);
        $this->trigger(self::EVENT_BEFORE_RENDER_TEMPLATE, $event);
        $variables = $event->variables;
        return $event->isValid;
    }

    /**
     * Performs actions after a template is rendered.
     *
     * @param mixed $template The name of the template that was rendered
     * @param array $variables The variables that were available to the template
     * @param string $output The template’s rendering result
     */
    public function afterRenderTemplate(string $template, array $variables, string &$output)
    {
        // Fire an 'afterRenderTemplate' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_RENDER_TEMPLATE)) {
            $event = new TemplateEvent([
                'template' => $template,
                'variables' => $variables,
                'output' => $output,
            ]);
            $this->trigger(self::EVENT_AFTER_RENDER_TEMPLATE, $event);
            $output = $event->output;
        }
    }

    /**
     * Performs actions before a page template is rendered.
     *
     * @param mixed $template The name of the template to render
     * @param array &$variables The variables that should be available to the template
     * @return bool Whether the template should be rendered
     */
    public function beforeRenderPageTemplate(string $template, array &$variables): bool
    {
        // Fire a 'beforeRenderPageTemplate' event
        $event = new TemplateEvent([
            'template' => $template,
            'variables' => &$variables,
        ]);
        $this->trigger(self::EVENT_BEFORE_RENDER_PAGE_TEMPLATE, $event);
        $variables = $event->variables;
        return $event->isValid;
    }

    /**
     * Performs actions after a page template is rendered.
     *
     * @param mixed $template The name of the template that was rendered
     * @param array $variables The variables that were available to the template
     * @param string $output The template’s rendering result
     */
    public function afterRenderPageTemplate(string $template, array $variables, string &$output)
    {
        // Fire an 'afterRenderPageTemplate' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_RENDER_PAGE_TEMPLATE)) {
            $event = new TemplateEvent([
                'template' => $template,
                'variables' => $variables,
                'output' => $output,
            ]);
            $this->trigger(self::EVENT_AFTER_RENDER_PAGE_TEMPLATE, $event);
            $output = $event->output;
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function renderHeadHtml()
    {
        $lines = [];
        if (!empty($this->title)) {
            $lines[] = '<title>' . Html::encode($this->title) . '</title>';
        }
        if (!empty($this->_scripts[self::POS_HEAD])) {
            $lines[] = implode("\n", $this->_scripts[self::POS_HEAD]);
        }

        $html = parent::renderHeadHtml();

        return empty($lines) ? $html : implode("\n", $lines) . $html;
    }

    /**
     * @inheritdoc
     */
    protected function renderBodyBeginHtml()
    {
        $lines = [];
        if (!empty($this->_scripts[self::POS_BEGIN])) {
            $lines[] = implode("\n", $this->_scripts[self::POS_BEGIN]);
        }

        $html = parent::renderBodyBeginHtml();

        return empty($lines) ? $html : implode("\n", $lines) . $html;
    }

    /**
     * @inheritdoc
     */
    protected function renderBodyEndHtml($ajaxMode)
    {
        $lines = [];
        if (!empty($this->_scripts[self::POS_END])) {
            $lines[] = implode("\n", $this->_scripts[self::POS_END]);
        }

        $html = parent::renderBodyEndHtml($ajaxMode);

        return empty($lines) ? $html : implode("\n", $lines) . $html;
    }

    /**
     * Registers any asset bundles and JS code that were queued-up in the session flash data.
     *
     * @throws Exception if any of the registered asset bundles are not actually asset bundles
     */
    protected function registerAssetFlashes()
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        $session = Craft::$app->getSession();

        if ($session->getIsActive()) {
            foreach ($session->getAssetBundleFlashes(true) as $name => $position) {
                if (!is_subclass_of($name, YiiAssetBundle::class)) {
                    throw new Exception("$name is not an asset bundle");
                }

                $this->registerAssetBundle($name, $position);
            }

            foreach ($session->getJsFlashes(true) as list($js, $position, $key)) {
                $this->registerJs($js, $position, $key);
            }
        }
    }

    /**
     * Registers all files provided by all registered asset bundles, including depending bundles files.
     *
     * Removes a bundle from [[assetBundles]] once files are registered.
     */
    protected function registerAllAssetFiles()
    {
        foreach ($this->assetBundles as $bundleName => $bundle) {
            $this->registerAssetFiles($bundleName);
        }
    }

    /**
     * @inheritdoc
     */
    protected function registerAssetFiles($name)
    {
        // Don't re-register bundles
        if (isset($this->_registeredAssetBundles[$name])) {
            return;
        }
        $this->_registeredAssetBundles[$name] = true;
        parent::registerAssetFiles($name);
    }

    // Private Methods
    // =========================================================================

    /**
     * Ensures that a template name isn't null, and that it doesn't lead outside the template folder. Borrowed from
     * [[Twig_Loader_Filesystem]].
     *
     * @param string $name
     * @throws \Twig_Error_Loader
     */
    private function _validateTemplateName(string $name)
    {
        if (StringHelper::contains($name, "\0")) {
            throw new \Twig_Error_Loader(Craft::t('app', 'A template name cannot contain NUL bytes.'));
        }

        if (Path::ensurePathIsContained($name) === false) {
            Craft::error('Someone tried to load a template outside the templates folder: ' . $name);
            throw new \Twig_Error_Loader(Craft::t('app', 'Looks like you are trying to load a template outside the template folder.'));
        }
    }

    /**
     * Searches for a template files, and returns the first match if there is one.
     *
     * @param string $basePath The base path to be looking in.
     * @param string $name The name of the template to be looking for.
     * @return string|null The matching file path, or `null`.
     */
    private function _resolveTemplate(string $basePath, string $name)
    {
        // Normalize the path and name
        $basePath = FileHelper::normalizePath($basePath);
        $name = trim(FileHelper::normalizePath($name), '/');

        // $name could be an empty string (e.g. to load the homepage template)
        if ($name) {
            // Maybe $name is already the full file path
            $testPath = $basePath . DIRECTORY_SEPARATOR . $name;

            if (is_file($testPath)) {
                return $testPath;
            }

            foreach ($this->_defaultTemplateExtensions as $extension) {
                $testPath = $basePath . DIRECTORY_SEPARATOR . $name . '.' . $extension;

                if (is_file($testPath)) {
                    return $testPath;
                }
            }
        }

        foreach ($this->_indexTemplateFilenames as $filename) {
            foreach ($this->_defaultTemplateExtensions as $extension) {
                $testPath = $basePath . ($name ? DIRECTORY_SEPARATOR . $name : '') . DIRECTORY_SEPARATOR . $filename . '.' . $extension;

                if (is_file($testPath)) {
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
    private function _getTwigOptions(): array
    {
        if ($this->_twigOptions !== null) {
            return $this->_twigOptions;
        }

        $this->_twigOptions = [
            'base_template_class' => Template::class,
            // See: https://github.com/twigphp/Twig/issues/1951
            'cache' => Craft::$app->getPath()->getCompiledTemplatesPath(),
            'auto_reload' => true,
            'charset' => Craft::$app->charset,
        ];

        if (YII_DEBUG) {
            $this->_twigOptions['debug'] = true;
            $this->_twigOptions['strict_variables'] = true;
        }

        return $this->_twigOptions;
    }

    /**
     * Returns any registered template roots.
     *
     * @param string $which 'cp' or 'site'
     * @return array
     */
    private function _getTemplateRoots(string $which): array
    {
        if (isset($this->_templateRoots[$which])) {
            return $this->_templateRoots[$which];
        }

        if ($which === 'cp') {
            $name = self::EVENT_REGISTER_CP_TEMPLATE_ROOTS;
        } else {
            $name = self::EVENT_REGISTER_SITE_TEMPLATE_ROOTS;
        }
        $event = new RegisterTemplateRootsEvent();
        $this->trigger($name, $event);

        $roots = [];

        foreach ($event->roots as $templatePath => $dir) {
            $templatePath = strtolower(trim($templatePath, '/'));
            $roots[$templatePath][] = $dir;
        }

        // Longest (most specific) first
        krsort($roots, SORT_STRING);

        return $this->_templateRoots[$which] = $roots;
    }

    /**
     * Replaces textarea contents with a marker.
     *
     * @param array $matches
     * @return string
     */
    private function _createTextareaMarker(array $matches): string
    {
        $marker = '{marker:' . StringHelper::randomString() . '}';
        $this->_textareaMarkers[$marker] = $matches[2];

        return $matches[1] . $marker . $matches[3];
    }

    private function _registeredJs($property, $names)
    {
        if (empty($names)) {
            return;
        }

        $js = "if (typeof Craft !== 'undefined') {\n";
        foreach (array_keys($names) as $name) {
            if ($name) {
                $jsName = Json::encode($name);
                $js .= "  Craft.{$property}[{$jsName}] = true;\n";
            }
        }
        $js .= '}';
        $this->registerJs($js, self::POS_HEAD);
    }

    /**
     * Returns the HTML for an element in the CP.
     *
     * @param array &$context
     * @return string|null
     */
    private function _getCpElementHtml(array &$context)
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
        if (isset($context['size']) && ($context['size'] === 'small' || $context['size'] === 'large')) {
            $elementSize = $context['size'];
        } else if (isset($context['viewMode']) && $context['viewMode'] === 'thumbs') {
            $elementSize = 'large';
        } else {
            $elementSize = 'small';
        }

        // Create the thumb/icon image, if there is one
        // ---------------------------------------------------------------------

        $thumbUrl = $element->getThumbUrl(self::$_elementThumbSizes[0]);

        if ($thumbUrl !== null) {
            $srcsets = [];

            foreach (self::$_elementThumbSizes as $i => $size) {
                if ($i == 0) {
                    $srcset = $thumbUrl;
                } else {
                    $srcset = $element->getThumbUrl($size);
                }

                $srcsets[] = $srcset . ' ' . $size . 'w';
            }

            $sizesHtml = ($elementSize === 'small' ? self::$_elementThumbSizes[0] : self::$_elementThumbSizes[2]) . 'px';
            $srcsetHtml = implode(', ', $srcsets);
            $imgHtml = "<div class='elementthumb' data-sizes='{$sizesHtml}' data-srcset='{$srcsetHtml}'></div>";
        } else {
            $imgHtml = '';
        }

        $htmlAttributes = array_merge(
            $element->getHtmlAttributes($context['context']),
            [
                'class' => 'element ' . $elementSize,
                'data-type' => get_class($element),
                'data-id' => $element->id,
                'data-site-id' => $element->siteId,
                'data-status' => $element->getStatus(),
                'data-label' => (string)$element,
                'data-url' => $element->getUrl(),
                'data-level' => $element->level,
            ]);

        if ($context['context'] === 'field') {
            $htmlAttributes['class'] .= ' removable';
        }

        if ($element::hasStatuses()) {
            $htmlAttributes['class'] .= ' hasstatus';
        }

        if ($thumbUrl !== null) {
            $htmlAttributes['class'] .= ' hasthumb';
        }

        $html = '<div';

        foreach ($htmlAttributes as $attribute => $value) {
            $html .= ' ' . $attribute . ($value !== null ? '="' . HtmlHelper::encode($value) . '"' : '');
        }

        if (ElementHelper::isElementEditable($element)) {
            $html .= ' data-editable';
        }

        $html .= '>';

        if ($context['context'] === 'field' && isset($context['name'])) {
            $html .= '<input type="hidden" name="' . $context['name'] . '[]" value="' . $element->id . '">';
            $html .= '<a class="delete icon" title="' . Craft::t('app', 'Remove') . '"></a> ';
        }

        if ($element::hasStatuses()) {
            $status = $element->getStatus();
            $statusClasses = $status . ' ' . ($element::statuses()[$status]['color'] ?? '');
            $html .= '<span class="status ' . $statusClasses . '"></span>';
        }

        $html .= $imgHtml;
        $html .= '<div class="label">';

        $html .= '<span class="title">';

        $label = HtmlHelper::encode($element);

        if ($context['context'] === 'index' && ($cpEditUrl = $element->getCpEditUrl())) {
            $cpEditUrl = HtmlHelper::encode($cpEditUrl);
            $html .= "<a href=\"{$cpEditUrl}\">{$label}</a>";
        } else {
            $html .= $label;
        }

        $html .= '</span></div></div>';

        return $html;
    }
}
