<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\events\CreateTwigEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\TemplateEvent;
use craft\helpers\App;
use craft\helpers\Cp;
use craft\helpers\FileHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Path;
use craft\helpers\StringHelper;
use craft\web\twig\CpExtension;
use craft\web\twig\Environment;
use craft\web\twig\Extension;
use craft\web\twig\GlobalsExtension;
use craft\web\twig\SinglePreloaderExtension;
use craft\web\twig\TemplateLoader;
use Throwable;
use Twig\Error\LoaderError as TwigLoaderError;
use Twig\Error\RuntimeError as TwigRuntimeError;
use Twig\Error\SyntaxError as TwigSyntaxError;
use Twig\Extension\CoreExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Extension\StringLoaderExtension;
use Twig\Template as TwigTemplate;
use Twig\TemplateWrapper;
use yii\base\Arrayable;
use yii\base\Exception;
use yii\base\Model;
use yii\base\NotSupportedException;
use yii\web\AssetBundle as YiiAssetBundle;

/**
 * @inheritdoc
 * @property string $templateMode the current template mode (either `site` or `cp`)
 * @property string $templatesPath the base path that templates should be found in
 * @property string|null $namespace the active namespace
 * @property-read array $cpTemplateRoots any registered control panel template roots
 * @property-read array $siteTemplateRoots any registered site template roots
 * @property-read bool $isRenderingPageTemplate whether a page template is currently being rendered
 * @property-read bool $isRenderingTemplate whether a template is currently being rendered
 * @property-read Environment $twig the Twig environment
 * @property-read string $bodyHtml the content to be inserted at the end of the body section
 * @property-read string $headHtml the content to be inserted in the head section
 * @property-write string[] $registeredAssetBundles the asset bundle names that should be marked as already registered
 * @property-write string[] $registeredJsFiles the JS files that should be marked as already registered
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class View extends \yii\web\View
{
    /**
     * @event CreateTwigEvent The event that is triggered when a Twig environment is created.
     * @see createTwig()
     * @since 4.3.0
     */
    public const EVENT_AFTER_CREATE_TWIG = 'afterCreateTwig';

    /**
     * @event RegisterTemplateRootsEvent The event that is triggered when registering control panel template roots
     */
    public const EVENT_REGISTER_CP_TEMPLATE_ROOTS = 'registerCpTemplateRoots';

    /**
     * @event RegisterTemplateRootsEvent The event that is triggered when registering site template roots
     */
    public const EVENT_REGISTER_SITE_TEMPLATE_ROOTS = 'registerSiteTemplateRoots';

    /**
     * @event TemplateEvent The event that is triggered before a template gets rendered
     */
    public const EVENT_BEFORE_RENDER_TEMPLATE = 'beforeRenderTemplate';

    /**
     * @event TemplateEvent The event that is triggered after a template gets rendered
     */
    public const EVENT_AFTER_RENDER_TEMPLATE = 'afterRenderTemplate';

    /**
     * @event TemplateEvent The event that is triggered before a page template gets rendered
     */
    public const EVENT_BEFORE_RENDER_PAGE_TEMPLATE = 'beforeRenderPageTemplate';

    /**
     * @event TemplateEvent The event that is triggered after a page template gets rendered
     */
    public const EVENT_AFTER_RENDER_PAGE_TEMPLATE = 'afterRenderPageTemplate';

    /**
     * @const TEMPLATE_MODE_CP
     */
    public const TEMPLATE_MODE_CP = 'cp';

    /**
     * @const TEMPLATE_MODE_SITE
     */
    public const TEMPLATE_MODE_SITE = 'site';

    /**
     * @var bool Whether to minify CSS registered with [[registerCss()]]
     * @since 3.4.0
     * @deprecated in 3.6.0.
     */
    public $minifyCss = false;

    /**
     * @var bool Whether to minify JS registered with [[registerJs()]]
     * @since 3.4.0
     * @deprecated in 3.6.0
     */
    public $minifyJs = false;

    /**
     * @var bool Whether to allow [[evaluateDynamicContent()]] to be called.
     *
     * ::: warning
     * Don’t enable this unless you have a *very* good reason to.
     * :::
     *
     * @since 3.5.0
     */
    public bool $allowEval = false;

    /**
     * @var Environment|null The Twig environment instance used for control panel templates
     */
    private ?Environment $_cpTwig = null;

    /**
     * @var Environment|null The Twig environment instance used for site templates
     */
    private ?Environment $_siteTwig = null;

    /**
     * @var array
     */
    private array $_twigOptions;

    /**
     * @var ExtensionInterface[] List of Twig extensions registered with [[registerTwigExtension()]]
     */
    private array $_twigExtensions = [];

    /**
     * @var string[]
     */
    private array $_templatePaths = [];

    /**
     * @var TemplateWrapper[]
     */
    private array $_objectTemplates = [];

    /**
     * @var string|null
     */
    private ?string $_templateMode = null;

    /**
     * @var array|null
     */
    private ?array $_cpTemplateRoots = null;

    /**
     * @var array|null
     */
    private ?array $_siteTemplateRoots = null;

    /**
     * @var array|null
     */
    private ?array $_templateRoots = null;

    /**
     * @var string|null The root path to look for templates in
     */
    private ?string $_templatesPath = null;

    /**
     * @var string[]
     */
    private array $_defaultTemplateExtensions;

    /**
     * @var string[]
     */
    private array $_indexTemplateFilenames;

    /**
     * @var string
     */
    private string $_privateTemplateTrigger;

    /**
     * @var string|null
     */
    private ?string $_namespace = null;

    /**
     * @var bool Whether delta input name registration is open.
     * @see getIsDeltaRegistrationActive()
     * @see setIsDeltaRegistrationActive()
     * @see registerDeltaName()
     */
    private bool $_registerDeltaNames = false;

    /**
     * @var string[] The registered delta input names.
     * @see registerDeltaName()
     */
    private array $_deltaNames = [];

    /**
     * @var array The initial delta input values.
     * @see setInitialDeltaValue()
     */
    private array $_initialDeltaValues = [];

    /**
     * @var array
     * @see startJsBuffer()
     * @see clearJsBuffer()
     */
    private array $_jsBuffers = [];

    /**
     * @var array
     * @see startScriptBuffer()
     * @see clearScriptBuffer()
     */
    private array $_scriptBuffers = [];

    /**
     * @var array
     * @see startCssBuffer()
     * @see clearCssBuffer()
     */
    private array $_cssBuffers = [];

    /**
     * @var array
     * @see startCssFileBuffer()
     * @see clearCssFileBuffer()
     */
    private array $_cssFileBuffers = [];

    /**
     * @var array
     * @see startJsFileBuffer()
     * @see clearJsFileBuffer()
     */
    private array $_jsFileBuffers = [];

    /**
     * @var array
     * @see startHtmlBuffer()
     * @see clearHtmlBuffer()
     */
    private array $_htmlBuffers = [];

    /**
     * @var array|null the registered generic `<script>` code blocks
     * @see registerScript()
     */
    private ?array $_scripts = null;

    /**
     * @var array the registered generic HTML code blocks
     * @see registerHtml()
     */
    private array $_html = [];

    /**
     * @var callable[][]
     */
    private array $_hooks = [];

    /**
     * @var string|null
     */
    private ?string $_renderingTemplate = null;

    /**
     * @var bool
     */
    private bool $_isRenderingPageTemplate = false;

    /**
     * @var string[]
     * @see registerAssetFiles()
     * @see setRegisteredAssetBundles()
     */
    private array $_registeredAssetBundles = [];

    /**
     * @var string[]
     * @see registerJsFile()
     * @see setRegisteredJsfiles()
     */
    private array $_registeredJsFiles = [];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        // Set the initial template mode based on whether this is a control panel or site request
        $request = Craft::$app->getRequest();
        if ($request->getIsConsoleRequest() || $request->getIsCpRequest()) {
            $this->setTemplateMode(self::TEMPLATE_MODE_CP);
        } else {
            $this->setTemplateMode(self::TEMPLATE_MODE_SITE);
        }

        // Register the control panel hooks
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
        // Log a warning if the app isn't fully initialized yet
        if (!Craft::$app->getIsInitialized()) {
            Craft::warning('Twig instantiated before Craft is fully initialized.', __METHOD__);
        }

        $twig = new Environment(new TemplateLoader($this), $this->_getTwigOptions());

        $twig->addExtension(new StringLoaderExtension());
        $twig->addExtension(new Extension($this, $twig));

        if ($this->_templateMode === self::TEMPLATE_MODE_CP) {
            $twig->addExtension(new CpExtension());
        } elseif (Craft::$app->getIsInstalled()) {
            $twig->addExtension(new GlobalsExtension());

            if (Craft::$app->getConfig()->getGeneral()->preloadSingles) {
                $twig->addExtension(new SinglePreloaderExtension());
            }
        }

        // Add plugin-supplied extensions
        foreach ($this->_twigExtensions as $extension) {
            $twig->addExtension($extension);
        }

        // Set our timezone
        /** @var CoreExtension $core */
        $core = $twig->getExtension(CoreExtension::class);
        $core->setTimezone(Craft::$app->getTimeZone());

        // Fire a afterCreateTwig event
        if ($this->hasEventHandlers(self::EVENT_AFTER_CREATE_TWIG)) {
            $this->trigger(self::EVENT_AFTER_CREATE_TWIG, new CreateTwigEvent([
                'templateMode' => $this->_templateMode ?? self::TEMPLATE_MODE_SITE,
                'twig' => $twig,
            ]));
        }

        return $twig;
    }

    /**
     * Registers a new Twig extension, which will be added on existing environments and queued up for future environments.
     *
     * @param ExtensionInterface $extension
     */
    public function registerTwigExtension(ExtensionInterface $extension): void
    {
        // Make sure this extension isn't already registered
        $class = get_class($extension);
        if (isset($this->_twigExtensions[$class])) {
            return;
        }

        $this->_twigExtensions[$class] = $extension;

        // Add it to any existing Twig environments
        if (isset($this->_cpTwig)) {
            $this->_cpTwig->addExtension($extension);
        }
        if (isset($this->_siteTwig)) {
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
        return isset($this->_renderingTemplate);
    }

    /**
     * Renders a Twig template.
     *
     * @param string $template The name of the template to load
     * @param array $variables The variables that should be available to the template
     * @param string|null $templateMode The template mode to use
     * @return string the rendering result
     * @throws TwigLoaderError
     * @throws TwigRuntimeError
     * @throws TwigSyntaxError
     * @throws Exception if $templateMode is invalid
     */
    public function renderTemplate(string $template, array $variables = [], ?string $templateMode = null): string
    {
        if ($templateMode === null) {
            $templateMode = $this->getTemplateMode();
        }

        if (!$this->beforeRenderTemplate($template, $variables, $templateMode)) {
            return '';
        }

        Craft::debug("Rendering template: $template", __METHOD__);

        $oldTemplateMode = $this->getTemplateMode();
        $this->setTemplateMode($templateMode);

        // Render and return
        $renderingTemplate = $this->_renderingTemplate;
        $this->_renderingTemplate = $template;

        try {
            $output = $this->getTwig()->render($template, $variables);
        } finally {
            $this->_renderingTemplate = $renderingTemplate;
            $this->setTemplateMode($oldTemplateMode);
        }

        $this->afterRenderTemplate($template, $variables, $templateMode, $output);
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
     * @param string|null $templateMode The template mode to use
     * @return string the rendering result
     * @throws TwigLoaderError
     * @throws TwigRuntimeError
     * @throws TwigSyntaxError
     * @throws Exception if $templateMode is invalid
     */
    public function renderPageTemplate(string $template, array $variables = [], ?string $templateMode = null): string
    {
        if ($templateMode === null) {
            $templateMode = $this->getTemplateMode();
        }

        if (!$this->beforeRenderPageTemplate($template, $variables, $templateMode)) {
            return '';
        }

        ob_start();
        ob_implicit_flush(false);

        $oldTemplateMode = $this->getTemplateMode();
        $this->setTemplateMode($templateMode);

        $isRenderingPageTemplate = $this->_isRenderingPageTemplate;
        $this->_isRenderingPageTemplate = true;

        try {
            $this->beginPage();
            echo $this->renderTemplate($template, $variables);
            $this->endPage();
        } finally {
            $this->_isRenderingPageTemplate = $isRenderingPageTemplate;
            $this->setTemplateMode($oldTemplateMode);
            $output = ob_get_clean();
        }

        $this->afterRenderPageTemplate($template, $variables, $templateMode, $output);
        return $output;
    }

    /**
     * Renders a template defined in a string.
     *
     * @param string $template The source template string.
     * @param array $variables Any variables that should be available to the template.
     * @param string $templateMode The template mode to use.
     * @param bool $escapeHtml Whether dynamic HTML should be escaped
     * @return string The rendered template.
     * @throws TwigLoaderError
     * @throws TwigSyntaxError
     */
    public function renderString(string $template, array $variables = [], string $templateMode = self::TEMPLATE_MODE_SITE, bool $escapeHtml = false): string
    {
        // If there are no dynamic tags, just return the template
        if (!str_contains($template, '{')) {
            return $template;
        }

        $oldTemplateMode = $this->templateMode;
        $this->setTemplateMode($templateMode);

        $twig = $this->getTwig();
        if (!$escapeHtml) {
            $twig->setDefaultEscaperStrategy(false);
        }
        $lastRenderingTemplate = $this->_renderingTemplate;
        $this->_renderingTemplate = 'string:' . $template;

        try {
            return $twig->createTemplate($template)->render($variables);
        } finally {
            $this->_renderingTemplate = $lastRenderingTemplate;
            if (!$escapeHtml) {
                $twig->setDefaultEscaperStrategy();
            }
            $this->setTemplateMode($oldTemplateMode);
        }
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
     * @param string $templateMode The template mode to use.
     * @return string The rendered template.
     * @throws Exception in case of failure
     * @throws Throwable in case of failure
     */
    public function renderObjectTemplate(string $template, mixed $object, array $variables = [], string $templateMode = self::TEMPLATE_MODE_SITE): string
    {
        // If there are no dynamic tags, just return the template
        if (!str_contains($template, '{')) {
            return trim($template);
        }

        $oldTemplateMode = $this->templateMode;
        $this->setTemplateMode($templateMode);
        $twig = $this->getTwig();

        // Temporarily disable strict variables if it's enabled
        $strictVariables = $twig->isStrictVariables();

        if ($strictVariables) {
            $twig->disableStrictVariables();
        }

        $twig->setDefaultEscaperStrategy(false);
        $lastRenderingTemplate = $this->_renderingTemplate;
        $this->_renderingTemplate = 'string:' . $template;

        try {
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
                    if (!isset($variables[$name]) && str_contains($template, $name)) {
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
                    if (preg_match('/\b' . preg_quote($field, '/') . '\b/', $template)) {
                        $extra[] = $field;
                    }
                }
                $variables += $object->toArray([], $extra, false);
            }

            $variables['object'] = $object;
            $variables['_variables'] = $variables;

            // Render it!
            /** @var TwigTemplate $templateObj */
            $templateObj = $this->_objectTemplates[$cacheKey];
            return trim($templateObj->render($variables));
        } finally {
            $this->_renderingTemplate = $lastRenderingTemplate;
            $twig->setDefaultEscaperStrategy();
            $this->setTemplateMode($oldTemplateMode);

            // Re-enable strict variables
            if ($strictVariables) {
                $twig->enableStrictVariables();
            }
        }
    }

    /**
     * Normalizes an object template for [[renderObjectTemplate()]].
     *
     * @param string $template
     * @return string
     */
    public function normalizeObjectTemplate(string $template): string
    {
        $tokens = [];

        // Tokenize {% verbatim %} ... {% endverbatim %} tags in their entirety
        $template = preg_replace_callback('/\{%-?\s*verbatim\s*-?%\}.*?{%-?\s*endverbatim\s*-?%\}/s',
            function(array $matches) use (&$tokens) {
                $token = 'tok_' . StringHelper::randomString(10);
                $tokens[$token] = $matches[0];
                return $token;
            },
            $template
        );

        // Tokenize any remaining Twig tags (including print tags)
        $template = preg_replace_callback('/\{%-?\s*\w+.*?%\}|(?<!\{)\{\{(?!\{).+?(?<!\})\}\}(?!\})/s',
            function(array $matches) use (&$tokens) {
                $token = 'tok_' . StringHelper::randomString(10);
                $tokens[$token] = $matches[0];
                return $token;
            },
            $template
        );

        // Tokenize inline code and code blocks
        $template = preg_replace_callback('/(?<!`)(`|`{3,})(?!`).*?(?<!`)\1(?!`)/s', function(array $matches) use (&$tokens) {
            $token = 'tok_' . StringHelper::randomString(10);
            $tokens[$token] = '{% verbatim %}' . $matches[0] . '{% endverbatim %}';
            return $token;
        }, $template);

        // Tokenize objects (call preg_replace_callback() multiple times in case there are nested objects)
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
        $template = preg_replace_callback('/(?<!\{)\{\s*(\w+)([^\{]*?)\}/', function(array $match) {
            // Is this a function call like `clone()`?
            if (!empty($match[2]) && $match[2][0] === '(') {
                $replace = $match[1] . $match[2];
            } else {
                $replace = "(_variables.$match[1] ?? object.$match[1])$match[2]";
            }
            return "{{ $replace|raw }}";
        }, $template);

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
     * @param string|null $templateMode The template mode to use.
     * @param bool $publicOnly Whether to only look for public templates (template paths that don’t start with the private template trigger).
     * @return bool Whether the template exists.
     * @throws Exception
     */
    public function doesTemplateExist(string $name, ?string $templateMode = null, bool $publicOnly = false): bool
    {
        try {
            return ($this->resolveTemplate($name, $templateMode, $publicOnly) !== false);
        } catch (TwigLoaderError) {
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
     * index filenames are configurable via the <config4:defaultTemplateExtensions>
     * and <config4:indexTemplateFilenames> config settings.
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
     * @param string $name The name of the template.
     * @param string|null $templateMode The template mode to use.
     * @param bool $publicOnly Whether to only look for public templates (template paths that don’t start with the private template trigger).
     * @return string|false The path to the template if it exists, or `false`.
     * @throws TwigLoaderError
     */
    public function resolveTemplate(string $name, ?string $templateMode = null, bool $publicOnly = false): string|false
    {
        if ($templateMode !== null) {
            $oldTemplateMode = $this->getTemplateMode();
            $this->setTemplateMode($templateMode);
        }

        try {
            return $this->_resolveTemplateInternal($name, $publicOnly);
        } finally {
            if (isset($oldTemplateMode)) {
                $this->setTemplateMode($oldTemplateMode);
            }
        }
    }

    /**
     * Finds a template on the file system and returns its path.
     *
     * @param string $name The name of the template.
     * @param bool $publicOnly Whether to only look for public templates (template paths that don’t start with the private template trigger).
     * @return string|false The path to the template if it exists, or `false`.
     * @throws TwigLoaderError
     */
    private function _resolveTemplateInternal(string $name, bool $publicOnly): string|false
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
            if (($path = $this->_resolveTemplate($basePath, $name, $publicOnly)) !== null) {
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
                if ($templateRoot === '' || strncasecmp($templateRoot . '/', $name . '/', $templateRootLen + 1) === 0) {
                    $subName = $templateRoot === '' ? $name : (strlen($name) === $templateRootLen ? '' : substr($name, $templateRootLen + 1));
                    foreach ($basePaths as $basePath) {
                        if (($path = $this->_resolveTemplate($basePath, $subName, $publicOnly)) !== null) {
                            return $this->_templatePaths[$key] = $path;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Returns any registered control panel template roots.
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
     * @inheritdoc
     */
    public function registerJs($js, $position = self::POS_READY, $key = null): void
    {
        // Trim any whitespace and ensure it ends with a semicolon.
        $js = StringHelper::ensureRight(trim($js, " \t\n\r\0\x0B"), ';');

        parent::registerJs($js, $position, $key);
    }

    /**
     * Registers JavaScript code with the given variables, pre-JSON-encoded.
     *
     * @param callable $jsFn callback function that returns the JS code to be registered.
     * @param array $vars Array of variables that will be JSON-encoded before being passed to `$jsFn`.
     * @param int $position the position at which the JS script tag should be inserted
     * in a page. The possible values are:
     *
     * - [[POS_HEAD]]: in the head section
     * - [[POS_BEGIN]]: at the beginning of the body section
     * - [[POS_END]]: at the end of the body section
     * - [[POS_LOAD]]: enclosed within jQuery(window).load().
     *   Note that by using this position, the method will automatically register the jQuery js file.
     * - [[POS_READY]]: enclosed within jQuery(document).ready(). This is the default value.
     *   Note that by using this position, the method will automatically register the jQuery js file.
     *
     * @param string|null $key the key that identifies the JS code block. If null, it will use
     * $js as the key. If two JS code blocks are registered with the same key, the latter
     * will overwrite the former.
     * @since 3.7.31
     */
    public function registerJsWithVars(callable $jsFn, array $vars, int $position = self::POS_READY, ?string $key = null): void
    {
        $jsVars = array_map(function($variable) {
            return Json::encode($variable);
        }, $vars);
        $js = call_user_func($jsFn, ...array_values($jsVars));
        $this->registerJs($js, $position, $key);
    }

    /**
     * Starts a buffer for any JavaScript code registered with [[registerJs()]].
     *
     * The buffer’s contents can be cleared and returned later via [[clearJsBuffer()]].
     *
     * @see clearJsBuffer()
     */
    public function startJsBuffer(): void
    {
        $this->_jsBuffers[] = $this->js;
        $this->js = [];
    }

    /**
     * Clears and ends a buffer started via [[startJsBuffer()]], returning any JavaScript code that was registered while
     * the buffer was active.
     *
     * @param bool $scriptTag Whether the returned JavaScript code should be wrapped in a `<script>` tag.
     * @param bool $combine Whether the JavaScript code should be returned in a combined blob. (Position and key info will be lost.)
     * @return string|array|false The JavaScript code that was registered while the buffer was active, or `false` if there wasn’t an active buffer.
     * @see startJsBuffer()
     */
    public function clearJsBuffer(bool $scriptTag = true, bool $combine = true): string|array|false
    {
        if (empty($this->_jsBuffers)) {
            return false;
        }

        $bufferedJs = $this->js;

        // Set the active queue to the last one
        $this->js = array_pop($this->_jsBuffers);

        if ($combine) {
            $js = '';

            foreach ([self::POS_HEAD, self::POS_BEGIN, self::POS_END, self::POS_LOAD, self::POS_READY] as $pos) {
                if (!empty($bufferedJs[$pos])) {
                    $js .= implode("\n", $bufferedJs[$pos]) . "\n";
                }
            }

            if ($scriptTag && !empty($js)) {
                return Html::script($js, ['type' => 'text/javascript']);
            }

            return $js;
        }

        if ($scriptTag) {
            foreach ($bufferedJs as $pos => $js) {
                $bufferedJs[$pos] = Html::script(implode("\n", $js), ['type' => 'text/javascript']);
            }
        }

        return $bufferedJs;
    }

    /**
     * Starts a buffer for any `<script>` tags registered with [[registerScript()]].
     *
     * The buffer’s contents can be cleared and returned later via [[clearScriptBuffer()]].
     *
     * @see clearScriptBuffer()
     * @since 3.7.0
     */
    public function startScriptBuffer(): void
    {
        $this->_scriptBuffers[] = $this->_scripts;
        $this->_scripts = [];
    }

    /**
     * Clears and ends a buffer started via [[startScriptBuffer()]], returning any `<script>` tags that were registered
     * while the buffer was active.
     *
     * @return array|false The `<script>` tags that were registered while the buffer was active, or `false` if there wasn’t an active buffer.
     * @see startScriptBuffer()
     * @since 3.7.0
     */
    public function clearScriptBuffer(): array|false
    {
        if (empty($this->_scriptBuffers)) {
            return false;
        }

        $bufferedScripts = $this->_scripts;
        $this->_scripts = array_pop($this->_scriptBuffers);
        return $bufferedScripts;
    }

    /**
     * Starts a buffer for any `<style>` tags registered with [[registerCss()]].
     *
     * The buffer’s contents can be cleared and returned later via [[clearCssBuffer()]].
     *
     * @see clearCssBuffer()
     * @since 3.7.0
     */
    public function startCssBuffer(): void
    {
        $this->_cssBuffers[] = $this->css;
        $this->css = [];
    }

    /**
     * Clears and ends a buffer started via [[startCssBuffer()]], returning any `<style>` tags that were registered
     * while the buffer was active.
     *
     * @return array|false The `<style>` tags that were registered while the buffer was active, or `false` if there wasn’t an active buffer.
     * @see startCssBuffer()
     * @since 3.7.0
     */
    public function clearCssBuffer(): array|false
    {
        if (empty($this->_cssBuffers)) {
            return false;
        }

        $bufferedCss = $this->css;
        $this->css = array_pop($this->_cssBuffers);
        return $bufferedCss;
    }

    /**
     * Starts a buffer for any `<link>` tags registered with [[registerCssFile()]].
     *
     * The buffer’s contents can be cleared and returned later via [[clearCssFileBuffer()]].
     *
     * @see clearCssFileBuffer()
     * @since 4.0.0
     */
    public function startCssFileBuffer(): void
    {
        $this->_cssFileBuffers[] = $this->cssFiles;
        $this->cssFiles = [];
    }

    /**
     * Clears and ends a buffer started via [[startCssFileBuffer()]], returning any `<link rel="stylesheet">` tags that were registered
     * while the buffer was active.
     *
     * @return array|false The `<link rel="stylesheet">` tags that were registered while the buffer was active, or `false` if there wasn’t an active buffer.
     * @see startCssFileBuffer()
     * @since 4.0.0
     */
    public function clearCssFileBuffer(): array|false
    {
        if (empty($this->_cssFileBuffers)) {
            return false;
        }

        $bufferedCssFiles = $this->cssFiles;
        $this->cssFiles = array_pop($this->_cssFileBuffers);
        return $bufferedCssFiles;
    }

    /**
     * Starts a buffer for any `<script>` tags registered with [[registerJsFile()]].
     *
     * The buffer’s contents can be cleared and returned later via [[clearJsFileBuffer()]].
     *
     * @see clearJsFileBuffer()
     * @since 4.0.0
     */
    public function startJsFileBuffer(): void
    {
        $this->_jsFileBuffers[] = $this->jsFiles;
        $this->jsFiles = [];
    }

    /**
     * Clears and ends a buffer started via [[startJsFileBuffer()]], returning any `<script>` tags that were registered
     * while the buffer was active.
     *
     * @return array|false The `<script>` tags that were registered while the buffer was active (indexed by position), or `false` if there wasn’t an active buffer.
     * @see startJsFileBuffer()
     * @since 4.0.0
     */
    public function clearJsFileBuffer(): array|false
    {
        if (empty($this->_jsFileBuffers)) {
            return false;
        }

        $bufferedJsFiles = $this->jsFiles;
        $this->jsFiles = array_pop($this->_jsFileBuffers);

        foreach ($bufferedJsFiles as $files) {
            foreach (array_keys($files) as $key) {
                unset($this->_registeredJsFiles[$key]);
            }
        }

        return $bufferedJsFiles;
    }

    /**
     * Starts a buffer for any html tags registered with [[registerHtml()]].
     *
     * @since 4.3.0
     */
    public function startHtmlBuffer(): void
    {
        $this->_htmlBuffers[] = $this->_html;
        $this->_html = [];
    }

    /**
     * Clears and ends a buffer started via [[startHtmlBuffer()]], returning any html tags that were registered
     * while the buffer was active.
     *
     * @return array|false The html that was registered while the buffer was active or `false` if there wasn't an active buffer.
     * @since 4.3.0
     */
    public function clearHtmlBuffer(): array|false
    {
        if (empty($this->_htmlBuffers)) {
            return false;
        }

        $bufferedHtml = $this->_html;
        $this->_html = array_pop($this->_htmlBuffers);
        return $bufferedHtml;
    }

    /**
     * @inheritdoc
     */
    public function registerJsFile($url, $options = [], $key = null): void
    {
        // If 'depends' is specified, ignore it for now because the file will
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
     * @param string|null $key the key that identifies the generic `<script>` code block. If null, it will use
     * $script as the key. If two generic `<script>` code blocks are registered with the same key, the latter
     * will overwrite the former.
     */
    public function registerScript(string $script, int $position = self::POS_END, array $options = [], ?string $key = null): void
    {
        $key = $key ?: md5($script);
        $this->_scripts[$position][$key] = Html::script($script, $options);
    }

    /**
     * Registers arbitrary HTML to be injected into the final page response.
     *
     * @param string $html the HTML code to be registered
     * @param int $position the position at which the HTML code should be inserted in the page. Possible values are:
     * - [[POS_HEAD]]: in the head section
     * - [[POS_BEGIN]]: at the beginning of the body section
     * - [[POS_END]]: at the end of the body section
     * @param string|null $key the key that identifies the HTML code. If null, it will use a hash of the HTML as the key.
     * If two HTML code blocks are registered with the same position and key, the latter will overwrite the former.
     * @since 3.5.0
     */
    public function registerHtml(string $html, int $position = self::POS_END, ?string $key = null): void
    {
        if ($key === null) {
            $key = md5($html);
        }
        $this->_html[$position][$key] = $html;
    }

    /**
     * @inheritdoc
     */
    public function endBody(): void
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
     * available for `Craft.t()` calls in the control panel.
     * Note this should always be called *before* any JavaScript is registered
     * that will need to use the translations, unless the JavaScript is
     * registered at [[\yii\web\View::POS_READY]].
     *
     * @param string $category The category the messages are in
     * @param string[] $messages The messages to be translated
     */
    public function registerTranslations(string $category, array $messages): void
    {
        $jsCategory = Json::encode($category);
        $js = '';

        foreach ($messages as $message) {
            $translation = Craft::t($category, $message);
            if ($translation !== $message) {
                $jsMessage = Json::encode($message);
                $jsTranslation = Json::encode($translation);
                $js .= ($js !== '' ? PHP_EOL : '') . "Craft.translations[$jsCategory][$jsMessage] = $jsTranslation;";
            }
        }

        if ($js === '') {
            return;
        }

        $js = <<<JS
if (typeof Craft.translations[$jsCategory] === 'undefined') {
    Craft.translations[$jsCategory] = {};
}
$js
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
    public function getNamespace(): ?string
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
    public function setNamespace(?string $namespace): void
    {
        $this->_namespace = $namespace;
    }

    /**
     * Registers a delta input name.
     *
     * This can be either the name of a single form input, or a prefix used by multiple input names.
     *
     * The input name will be namespaced with the currently active [[getNamespace()|namespace]], if any.
     *
     * When a form that supports delta updates is submitted, any delta inputs (or groups of inputs) that didn’t change
     * over the lifespan of the page will be omitted from the POST request.
     *
     * Note that delta input names will only be registered if delta registration is active
     * (see [[getIsDeltaRegistrationActive()]]).
     *
     * @param string $inputName
     * @since 3.4.0
     */
    public function registerDeltaName(string $inputName): void
    {
        if ($this->_registerDeltaNames) {
            $this->_deltaNames[] = $this->namespaceInputName($inputName);
        }
    }

    /**
     * Returns the initial values of delta inputs.
     *
     * @return array
     * @see setInitialDeltaValue()
     * @since 3.7.0
     */
    public function getInitialDeltaValues(): array
    {
        return $this->_initialDeltaValues;
    }

    /**
     * Sets the initial value of a delta input name.
     *
     * @param string $inputName
     * @param mixed $value
     * @see getInitialDeltaValues()
     * @since 3.4.6
     */
    public function setInitialDeltaValue(string $inputName, mixed $value): void
    {
        if ($this->_registerDeltaNames) {
            $this->_initialDeltaValues[$this->namespaceInputName($inputName)] = $value;
        }
    }

    /**
     * Returns whether delta input name registration is currently active
     *
     * @return bool
     * @see registerDeltaName()
     * @since 3.4.0
     */
    public function getIsDeltaRegistrationActive(): bool
    {
        return $this->_registerDeltaNames;
    }

    /**
     * Sets whether delta input name registration is active.
     *
     * @param bool $active
     * @see registerDeltaName()
     * @since 3.4.0
     */
    public function setIsDeltaRegistrationActive(bool $active): void
    {
        $this->_registerDeltaNames = $active;
    }

    /**
     * Returns all of the registered delta input names.
     *
     * @return string[]
     * @since 3.4.0
     * @see registerDeltaName()
     */
    public function getDeltaNames(): array
    {
        return $this->_deltaNames;
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
     * - the "index" template filenames that should be checked when looking for templates
     *
     * @param string $templateMode Either 'site' or 'cp'
     * @throws Exception if $templateMode is invalid
     */
    public function setTemplateMode(string $templateMode): void
    {
        // Ignore if it's already set to that
        if ($templateMode === $this->_templateMode) {
            return;
        }

        // Validate
        if (!in_array($templateMode, [
            self::TEMPLATE_MODE_CP,
            self::TEMPLATE_MODE_SITE,
        ], true)
        ) {
            throw new Exception('"' . $templateMode . '" is not a valid template mode');
        }

        // Set the new template mode
        $this->_templateMode = $templateMode;

        // Update everything
        if ($templateMode == self::TEMPLATE_MODE_CP) {
            $this->setTemplatesPath(Craft::$app->getPath()->getCpTemplatesPath());
            $this->_defaultTemplateExtensions = ['twig', 'html'];
            $this->_indexTemplateFilenames = ['index'];
            $this->_privateTemplateTrigger = '_';
        } else {
            $this->setTemplatesPath(Craft::$app->getPath()->getSiteTemplatesPath());
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $this->_defaultTemplateExtensions = $generalConfig->defaultTemplateExtensions;
            $this->_indexTemplateFilenames = $generalConfig->indexTemplateFilenames;
            $this->_privateTemplateTrigger = $generalConfig->privateTemplateTrigger;
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
    public function setTemplatesPath(string $templatesPath): void
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
     * <input type="text" name="bar[title]" id="bar-title">
     * ```
     *
     * would become:
     *
     * ```html
     * <label for="foo-bar-title">Title</label>
     * <input type="text" name="foo[bar][title]" id="foo-bar-title">
     * ```
     *
     * When a callable is passed to `$html` (supported as of Craft 3.7), the namespace will be set via
     * [[setNamespace()]] before the callable is executed, in time for any JavaScript code that needs to be
     * registered by the callable.
     *
     * ```php
     * $settingsHtml = Craft::$app->view->namespaceInputs(function() use ($widget) {
     *     return $widget->getSettingsHtml();
     * }, 'widget-settings');
     * ```
     *
     * @param callable|string $html The HTML code, or a callable that returns the HTML code
     * @param string|null $namespace The namespace. Defaults to the [[getNamespace()|active namespace]].
     * @param bool $otherAttributes Whether `id`, `for`, and other attributes should be namespaced (in addition to `name`)
     * @param bool $withClasses Whether class names should be namespaced as well (affects both `class` attributes and
     * class name CSS selectors within `<style>` tags). This will only have an effect if `$otherAttributes` is `true`.
     * @return string The HTML with namespaced attributes
     */
    public function namespaceInputs(callable|string $html, ?string $namespace = null, bool $otherAttributes = true, bool $withClasses = false): string
    {
        if (is_callable($html)) {
            // If no namespace was passed in, just return the callable response directly.
            // No need to namespace it via the currently-set namespace in this case; if there is one, it should get applied later on.
            if ($namespace === null) {
                return $html();
            }

            $oldNamespace = $this->getNamespace();
            $this->setNamespace($this->namespaceInputName($namespace));
            try {
                $response = $this->namespaceInputs($html(), $namespace, $otherAttributes, $withClasses);
            } finally {
                $this->setNamespace($oldNamespace);
            }
            return $response;
        }

        if ($html === '') {
            return $html;
        }

        if ($namespace === null) {
            $namespace = $this->getNamespace();
            // If there's no active namespace, we're done here
            if ($namespace === null) {
                return $html;
            }
        }

        return $otherAttributes
            ? Html::namespaceHtml($html, $namespace, $withClasses)
            : Html::namespaceInputs($html, $namespace);
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
    public function namespaceInputName(string $inputName, ?string $namespace = null): string
    {
        if ($inputName === '') {
            return $inputName;
        }

        if ($namespace === null) {
            $namespace = $this->getNamespace();
            if ($namespace === null) {
                return $inputName;
            }
        }

        return Html::namespaceInputName($inputName, $namespace);
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
    public function namespaceInputId(string $inputId, ?string $namespace = null): string
    {
        if ($inputId === '') {
            return $inputId;
        }

        if ($namespace === null) {
            $namespace = $this->getNamespace();
            if ($namespace === null) {
                return Html::id($inputId);
            }
        }

        return Html::namespaceId($inputId, $namespace);
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
     * @deprecated in 3.5.0. Use [[Html::id()]] instead.
     */
    public function formatInputId(string $inputName): string
    {
        if ($inputName === '') {
            return $inputName;
        }

        return Html::id($inputName);
    }

    /**
     * Queues up a method to be called by a given template hook.
     *
     * For example, if you place this in your plugin’s [[\craft\base\Plugin::init()|init()]] method:
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
     * When the hook tag gets invoked, your template hook function will get called. The `$context` argument will be the
     * current Twig context array, which you’re free to manipulate. Any changes you make to it will be available to the
     * template following the tag. Whatever your template hook function returns will be output in place of the tag in
     * the template as well.
     *
     * If you want to prevent additional hook methods from getting triggered, add a second `$handled` argument to your callback method,
     * which should be passed by reference, and then set it to `true` within the method.
     *
     * ```php
     * Craft::$app->view->hook('myAwesomeHook', function(&$context, &$handled) {
     *     $context['foo'] = 'bar';
     *     $handled = true;
     *     return 'Hey!';
     * });
     * ```
     *
     * @param string $hook The hook name.
     * @param callable $method The callback function.
     * @param bool $append whether to append the method handler to the end of the existing method list for the hook. If `false`, the method will be
     * inserted at the beginning of the existing method list.
     */
    public function hook(string $hook, callable $method, bool $append = true): void
    {
        if ($append || empty($this->_hooks[$hook])) {
            $this->_hooks[$hook][] = $method;
        } else {
            array_unshift($this->_hooks[$hook], $method);
        }
    }

    /**
     * Invokes a template hook.
     *
     * This is called by [[HookNode|`{% hook %}` tags]].
     *
     * @param string $hook The hook name.
     * @param array $context The current template context.
     * @return string Whatever the hooks returned.
     */
    public function invokeHook(string $hook, array &$context): string
    {
        $return = '';

        if (isset($this->_hooks[$hook])) {
            $handled = false;
            foreach ($this->_hooks[$hook] as $method) {
                $return .= $method($context, $handled);
                /** @var bool $handled */
                if ($handled) {
                    break;
                }
            }
        }

        return $return;
    }

    /**
     * Sets the JS files that should be marked as already registered.
     *
     * @param string[] $keys
     * @since 3.0.10
     */
    public function setRegisteredJsFiles(array $keys): void
    {
        $this->_registeredJsFiles = array_flip($keys);
    }

    /**
     * Sets the asset bundle names that should be marked as already registered.
     *
     * @param string[] $names Asset bundle names
     * @since 3.0.10
     */
    public function setRegisteredAssetBundles(array $names): void
    {
        $this->_registeredAssetBundles = array_flip($names);
    }

    /**
     * @inheritdoc
     */
    public function endPage($ajaxMode = false): void
    {
        if (!$ajaxMode && Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_setJsProperty('registeredJsFiles', $this->_registeredJsFiles);
            $this->_setJsProperty('registeredAssetBundles', $this->_registeredAssetBundles);
        }

        parent::endPage($ajaxMode);
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException unless [[allowEval]] has been set to `true`.
     */
    public function evaluateDynamicContent($statements)
    {
        if (!$this->allowEval) {
            throw new NotSupportedException('evaluateDynamicContent() is disallowed.');
        }

        return parent::evaluateDynamicContent($statements);
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * Performs actions before a template is rendered.
     *
     * @param string $template The name of the template to render
     * @param array $variables The variables that should be available to the template
     * @param string $templateMode The template mode to use when rendering the template
     * @return bool Whether the template should be rendered
     */
    public function beforeRenderTemplate(string $template, array &$variables, string &$templateMode): bool
    {
        // Fire a 'beforeRenderTemplate' event
        $event = new TemplateEvent([
            'template' => $template,
            'variables' => $variables,
            'templateMode' => $templateMode,
        ]);
        $this->trigger(self::EVENT_BEFORE_RENDER_TEMPLATE, $event);

        $variables = $event->variables;
        $templateMode = $event->templateMode;

        return $event->isValid;
    }

    /**
     * Performs actions after a template is rendered.
     *
     * @param string $template The name of the template that was rendered
     * @param array $variables The variables that were available to the template
     * @param string $templateMode The template mode that was used when rendering the template
     * @param string $output The template’s rendering result
     */
    public function afterRenderTemplate(string $template, array $variables, string $templateMode, string &$output): void
    {
        // Fire an 'afterRenderTemplate' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_RENDER_TEMPLATE)) {
            $event = new TemplateEvent([
                'template' => $template,
                'variables' => $variables,
                'templateMode' => $templateMode,
                'output' => $output,
            ]);
            $this->trigger(self::EVENT_AFTER_RENDER_TEMPLATE, $event);

            $output = $event->output;
        }
    }

    /**
     * Performs actions before a page template is rendered.
     *
     * @param string $template The name of the template to render
     * @param array $variables The variables that should be available to the template
     * @param string $templateMode The template mode to use when rendering the template
     * @return bool Whether the template should be rendered
     */
    public function beforeRenderPageTemplate(string $template, array &$variables, string &$templateMode): bool
    {
        // Fire a 'beforeRenderPageTemplate' event
        $event = new TemplateEvent([
            'template' => $template,
            'variables' => &$variables,
            'templateMode' => $templateMode,
        ]);
        $this->trigger(self::EVENT_BEFORE_RENDER_PAGE_TEMPLATE, $event);

        $variables = $event->variables;
        $templateMode = $event->templateMode;

        return $event->isValid;
    }

    /**
     * Performs actions after a page template is rendered.
     *
     * @param string $template The name of the template that was rendered
     * @param array $variables The variables that were available to the template
     * @param string $templateMode The template mode that was used when rendering the template
     * @param string $output The template’s rendering result
     */
    public function afterRenderPageTemplate(string $template, array $variables, string $templateMode, string &$output): void
    {
        // Fire an 'afterRenderPageTemplate' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_RENDER_PAGE_TEMPLATE)) {
            $event = new TemplateEvent([
                'template' => $template,
                'variables' => $variables,
                'templateMode' => $templateMode,
                'output' => $output,
            ]);
            $this->trigger(self::EVENT_AFTER_RENDER_PAGE_TEMPLATE, $event);

            $output = $event->output;
        }
    }

    /**
     * @inheritdoc
     */
    protected function renderHeadHtml(): string
    {
        $lines = [];
        if (!empty($this->title)) {
            $lines[] = '<title>' . Html::encode($this->title) . '</title>';
        }
        if (!empty($this->_scripts[self::POS_HEAD])) {
            $lines[] = implode("\n", $this->_scripts[self::POS_HEAD]);
        }
        if (!empty($this->_html[self::POS_HEAD])) {
            $lines[] = implode("\n", $this->_html[self::POS_HEAD]);
        }

        $html = parent::renderHeadHtml();

        return empty($lines) ? $html : implode("\n", $lines) . $html;
    }

    /**
     * @inheritdoc
     */
    protected function renderBodyBeginHtml(): string
    {
        $lines = [];
        if (!empty($this->_scripts[self::POS_BEGIN])) {
            $lines[] = implode("\n", $this->_scripts[self::POS_BEGIN]);
        }
        if (!empty($this->_html[self::POS_BEGIN])) {
            $lines[] = implode("\n", $this->_html[self::POS_BEGIN]);
        }

        $html = parent::renderBodyBeginHtml();

        return empty($lines) ? $html : implode("\n", $lines) . $html;
    }

    /**
     * @inheritdoc
     */
    protected function renderBodyEndHtml($ajaxMode): string
    {
        $lines = [];
        if (!empty($this->_scripts[self::POS_END])) {
            $lines[] = implode("\n", $this->_scripts[self::POS_END]);
        }
        if (!empty($this->_html[self::POS_END])) {
            $lines[] = implode("\n", $this->_html[self::POS_END]);
        }

        $html = parent::renderBodyEndHtml($ajaxMode);

        return empty($lines) ? $html : implode("\n", $lines) . $html;
    }

    /**
     * Registers any asset bundles and JS code that were queued-up in the session flash data.
     *
     * @throws Exception if any of the registered asset bundles are not actually asset bundles
     */
    protected function registerAssetFlashes(): void
    {
        if (!Craft::$app->getRequest()->getIsCpRequest()) {
            return;
        }

        // Explicitly check if the session is active here, in case the session was closed.
        $session = Craft::$app->getSession();
        if ($session->getIsActive()) {
            foreach ($session->getAssetBundleFlashes(true) as $name => $position) {
                if (!is_subclass_of($name, YiiAssetBundle::class)) {
                    throw new Exception("$name is not an asset bundle");
                }

                $this->registerAssetBundle($name, $position);
            }

            foreach ($session->getJsFlashes(true) as [$js, $position, $key]) {
                $this->registerJs($js, $position, $key);
            }
        }
    }

    /**
     * Registers all files provided by all registered asset bundles, including depending bundles files.
     *
     * Removes a bundle from [[assetBundles]] once files are registered.
     *
     */
    protected function registerAllAssetFiles(): void
    {
        foreach ($this->assetBundles as $bundleName => $bundle) {
            $this->registerAssetFiles($bundleName);
        }
    }

    /**
     * @inheritdoc
     */
    protected function registerAssetFiles($name): void
    {
        // Don't re-register bundles
        if (isset($this->_registeredAssetBundles[$name])) {
            return;
        }
        $this->_registeredAssetBundles[$name] = true;
        parent::registerAssetFiles($name);
    }

    /**
     * Ensures that a template name isn't null, and that it doesn't lead outside the template folder. Borrowed from
     * [[\Twig\Loader\FilesystemLoader]].
     *
     * @param string $name
     * @throws TwigLoaderError
     */
    private function _validateTemplateName(string $name): void
    {
        if (StringHelper::contains($name, "\0")) {
            throw new TwigLoaderError(Craft::t('app', 'A template name cannot contain NUL bytes.'));
        }

        if (Path::ensurePathIsContained($name) === false) {
            Craft::warning('Someone tried to load a template outside the templates folder: ' . $name);
            throw new TwigLoaderError(Craft::t('app', 'Looks like you are trying to load a template outside the template folder.'));
        }
    }

    /**
     * Searches for a template files, and returns the first match if there is one.
     *
     * @param string $basePath The base path to be looking in.
     * @param string $name The name of the template to be looking for.
     * @param bool $publicOnly Whether to only look for public templates (template paths that don’t start with the private template trigger).
     * @return string|null The matching file path, or `null`.
     */
    private function _resolveTemplate(string $basePath, string $name, bool $publicOnly): ?string
    {
        // Normalize the path and name
        $basePath = FileHelper::normalizePath($basePath);
        $name = trim(FileHelper::normalizePath($name), '/');

        // $name could be an empty string (e.g. to load the homepage template)
        if ($name !== '') {
            if ($publicOnly && preg_match(sprintf('/(^|\/)%s/', preg_quote($this->_privateTemplateTrigger, '/')), $name)) {
                return null;
            }

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
                $testPath = $basePath . ($name !== '' ? DIRECTORY_SEPARATOR . $name : '') . DIRECTORY_SEPARATOR . $filename . '.' . $extension;

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
        if (isset($this->_twigOptions)) {
            return $this->_twigOptions;
        }

        $this->_twigOptions = [
            // See: https://github.com/twigphp/Twig/issues/1951
            'cache' => Craft::$app->getPath()->getCompiledTemplatesPath(),
            'auto_reload' => true,
            'charset' => Craft::$app->charset,
        ];

        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if ($generalConfig->headlessMode && Craft::$app->getRequest()->getIsSiteRequest()) {
            $this->_twigOptions['autoescape'] = 'js';
        }

        if (App::devMode()) {
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
            if (!isset($roots[$templatePath])) {
                $roots[$templatePath] = [];
            }
            array_push($roots[$templatePath], ...(array)$dir);
        }

        // Longest (most specific) first
        krsort($roots, SORT_STRING);

        return $this->_templateRoots[$which] = $roots;
    }

    /**
     * @param string $property
     * @param string[] $names
     */
    private function _setJsProperty(string $property, array $names): void
    {
        if (empty($names)) {
            return;
        }

        $js = "if (typeof Craft !== 'undefined') {\n";
        foreach (array_keys($names) as $name) {
            if ($name) {
                $jsName = Json::encode(str_replace(['<', '>'], '', $name));
                // WARNING: the curly braces are needed here no matter what PhpStorm thinks
                // https://youtrack.jetbrains.com/issue/WI-60044
                $js .= "  Craft.{$property}[$jsName] = true;\n";
            }
        }
        $js .= '}';
        $this->registerJs($js, self::POS_HEAD);
    }

    /**
     * Returns the HTML for an element in the control panel.
     *
     * @param array $context
     * @return string|null
     */
    private function _getCpElementHtml(array $context): ?string
    {
        if (!isset($context['element'])) {
            return null;
        }

        if (isset($context['size']) && in_array($context['size'], [Cp::ELEMENT_SIZE_SMALL, Cp::ELEMENT_SIZE_LARGE], true)) {
            $size = $context['size'];
        } else {
            $size = (isset($context['viewMode']) && $context['viewMode'] === 'thumbs') ? Cp::ELEMENT_SIZE_LARGE : Cp::ELEMENT_SIZE_SMALL;
        }

        return Cp::elementHtml(
            $context['element'],
            $context['context'] ?? 'index',
            $size,
            $context['name'] ?? null,
            true,
            true,
            true,
            true,
            $context['single'] ?? false,
            $context['autoReload'] ?? true,
        );
    }
}
