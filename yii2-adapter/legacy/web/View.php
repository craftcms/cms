<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\base\Event as YiiEvent;
use craft\events\AssetBundleEvent;
use craft\events\CreateTwigEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\TemplateEvent;
use craft\helpers\Cp;
use craft\helpers\FileHelper;
use craft\helpers\Path;
use craft\web\twig\CpExtension;
use craft\web\twig\Environment;
use craft\web\twig\Extension;
use craft\web\twig\FeExtension;
use craft\web\twig\SafeHtml;
use craft\web\twig\SecurityPolicy;
use craft\web\twig\SinglePreloaderExtension;
use craft\web\twig\TemplateLoader;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\View\AssetRegistry;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\Events\RegisterCpTemplateRoots;
use CraftCms\Cms\View\Events\RegisterSiteTemplateRoots;
use CraftCms\Cms\View\TemplateHooks;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use LogicException;
use Throwable;
use Twig\Error\LoaderError as TwigLoaderError;
use Twig\Error\RuntimeError as TwigRuntimeError;
use Twig\Error\SyntaxError as TwigSyntaxError;
use Twig\Extension\CoreExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Extension\SandboxExtension;
use Twig\Extension\StringLoaderExtension;
use Twig\Runtime\EscaperRuntime;
use Twig\Template as TwigTemplate;
use Twig\TemplateWrapper;
use yii\base\Arrayable;
use yii\base\Exception;
use yii\base\Model;
use yii\base\NotSupportedException;
use yii\web\AssetBundle as YiiAssetBundle;
use function CraftCms\Cms\t;

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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\Events\RegisterCpTemplateRoots} instead.
     */
    public const EVENT_REGISTER_CP_TEMPLATE_ROOTS = 'registerCpTemplateRoots';

    /**
     * @event RegisterTemplateRootsEvent The event that is triggered when registering site template roots
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\Events\RegisterSiteTemplateRoots} instead.
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
     * @event AssetBundleEvent The event that is triggered after an asset bundle is registered
     * @since 4.5.0
     */
    public const EVENT_AFTER_REGISTER_ASSET_BUNDLE = 'afterRegisterAssetBundle';

    /**
     * @const TEMPLATE_MODE_CP
     * @deprecated 6.0.0 use {@see TemplateMode::Cp} instead.
     */
    public const TEMPLATE_MODE_CP = 'cp';

    /**
     * @const TEMPLATE_MODE_SITE
     * @deprecated 6.0.0 use {@see TemplateMode::Site} instead.
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
     * @var array<class-string<ExtensionInterface>,ExtensionInterface>
     * @see registerCpTwigExtension()
     */
    private array $_cpTwigExtensions = [];

    /**
     * @var array<class-string<ExtensionInterface>,ExtensionInterface>
     * @see registerSiteTwigExtension()
     */
    private array $_siteTwigExtensions = [];

    /**
     * @var string[]
     */
    private array $_templatePaths = [];

    /**
     * @var TemplateWrapper[]
     */
    private array $_objectTemplates = [];

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
     * @var string[] The registered modified delta input names.
     * @see registerDeltaName()
     */
    private array $_modifiedDeltaNames = [];

    /**
     * @var array The initial delta input values.
     * @see setInitialDeltaValue()
     */
    private array $_initialDeltaValues = [];

    /**
     * @var int JS buffer depth counter — tracks nesting level for startJsBuffer/clearJsBuffer.
     */
    private int $_jsBufferDepth = 0;

    /**
     * @var int Script buffer depth counter.
     */
    private int $_scriptBufferDepth = 0;

    /**
     * @var int CSS buffer depth counter.
     */
    private int $_cssBufferDepth = 0;

    /**
     * @var int CSS file buffer depth counter.
     */
    private int $_cssFileBufferDepth = 0;

    /**
     * @var int JS file buffer depth counter.
     */
    private int $_jsFileBufferDepth = 0;

    /**
     * @var int HTML buffer depth counter.
     */
    private int $_htmlBufferDepth = 0;

    /**
     * @var int Meta tag buffer depth counter.
     */
    private int $_metaTagBufferDepth = 0;

    /**
     * @var array
     * @see startAssetBundleBuffer()
     * @see clearAssetBundleBuffer()
     */
    private array $_assetBundleBuffers = [];

    /**
     * @var int JS import buffer depth counter.
     */
    private int $_jsImportBufferDepth = 0;

    /**
     * @var array<string, string> JS registered at POS_READY, keyed by key.
     * These are kept in the adapter (not the registry) because they require
     * jQuery wrapping at render time.
     */
    private array $_readyJs = [];

    /**
     * @var array<string, string> JS registered at POS_LOAD, keyed by key.
     * These are kept in the adapter (not the registry) because they require
     * jQuery wrapping at render time.
     */
    private array $_loadJs = [];

    /**
     * @var array<string, string> JS registered at POS_BEGIN, keyed by key.
     * These are kept in the adapter (not the registry) because the registry
     * only has Head and Body positions — POS_BEGIN content must render at
     * the body-begin placeholder, separate from body-end content.
     */
    private array $_beginJs = [];

    /**
     * @var array<string, string> HTML registered at POS_BEGIN, keyed by key.
     * Kept in the adapter for the same reason as $_beginJs.
     */
    private array $_beginHtml = [];

    /**
     * @var array<string, string> Script tags registered at POS_BEGIN, keyed by key.
     * Kept in the adapter for the same reason as $_beginJs.
     */
    private array $_beginScripts = [];

    /**
     * @var array<string, string> JS file tags registered at POS_BEGIN, keyed by key.
     * Kept in the adapter for the same reason as $_beginJs.
     */
    private array $_beginJsFiles = [];

    /**
     * @var list<array{ready: array<string, string>, load: array<string, string>, begin: array<string, string>}>
     * Buffer stack for POS_READY/POS_LOAD/POS_BEGIN JS.
     */
    private array $_readyLoadBuffers = [];

    /**
     * @var list<array<string, string>> Buffer stack for $_beginScripts.
     */
    private array $_scriptBeginBuffers = [];

    /**
     * @var list<array<string, string>> Buffer stack for $_beginJsFiles.
     */
    private array $_jsFileBeginBuffers = [];

    /**
     * @var list<array<string, string>> Buffer stack for $_beginHtml.
     */
    private array $_htmlBeginBuffers = [];

    /**
     * @var array<string, int> Maps JS keys to their original Yii2 position (POS_HEAD or POS_BEGIN)
     * when both map to Position::Head. Used by clearJsBuffer to reconstruct accurate position keys.
     */
    private array $_jsOriginalPositions = [];

    /**
     * @var list<array<string, int>> Buffer stack for $_jsOriginalPositions.
     */
    private array $_jsOriginalPositionBuffers = [];

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

    private ?AssetRegistry $_registry = null;

    private function registry(): AssetRegistry
    {
        return $this->_registry ??= app(AssetRegistry::class);
    }

    private ?TemplateHooks $_templateHooks = null;

    private function templateHooks(): TemplateHooks
    {
        return $this->_templateHooks ??= app(TemplateHooks::class);
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        // Register the control panel hooks
        $this->hook('cp.elements.element', [$this, 'elementChipHtml']);
    }

    /**
     * Returns the Twig environment.
     *
     * @param string|null $templateMode
     * @return Environment
     */
    public function getTwig(?string $templateMode = null): Environment
    {
        $mode = TemplateMode::tryFrom($templateMode) ?? TemplateMode::get();

        return $mode === TemplateMode::Cp
            ? $this->_cpTwig ?? ($this->_cpTwig = $this->createTwig())
            : $this->_siteTwig ?? ($this->_siteTwig = $this->createTwig());
    }

    /**
     * Sets the Twig environment for the current template mode.
     *
     * @param Environment $twig
     * @since 5.6.0
     */
    public function setTwig(Environment $twig): void
    {
        if (TemplateMode::is(TemplateMode::Cp)) {
            $this->_cpTwig = $twig;
        } else {
            $this->_siteTwig = $twig;
        }
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
            Log::warning('Twig instantiated before Craft is fully initialized.', [__METHOD__]);
        }

        $twig = new Environment(new TemplateLoader($this), $this->_getTwigOptions());

        // Mark SafeHtml as a safe interface
        $safeClass = SafeHtml::class;
        /** @phpstan-ignore argument.type */
        $twig->getRuntime(EscaperRuntime::class)->addSafeClass($safeClass, ['html']);

        $twig->addExtension(new StringLoaderExtension());
        $twig->addExtension(new Extension($this, $twig));

        if (TemplateMode::is(TemplateMode::Cp)) {
            $twig->addExtension(new CpExtension());
        } elseif (Cms::isInstalled()) {
            $twig->addExtension(new FeExtension());

            if (Cms::config()->preloadSingles) {
                $twig->addExtension(new SinglePreloaderExtension());
            }
        }

        // Add plugin-supplied extensions
        $registeredExtensions = TemplateMode::is(TemplateMode::Cp)
            ? $this->_cpTwigExtensions
            : $this->_siteTwigExtensions;
        foreach ($registeredExtensions as $extension) {
            $twig->addExtension($extension);
        }

        // Only register the SandboxExtension if something else hasn't already
        if (!$twig->hasExtension(SandboxExtension::class)) {
            $sandboxConfig = config('craft.twig-sandbox', []);
            $twig->addExtension(new SandboxExtension(new SecurityPolicy(
                $sandboxConfig['allowedTags'],
                $sandboxConfig['allowedFilters'],
                $sandboxConfig['allowedFunctions'],
                $sandboxConfig['allowedMethods'],
                $sandboxConfig['allowedProperties'],
                $sandboxConfig['allowedClasses'],
            )));
        }

        // Set our timezone
        /** @var CoreExtension $core */
        $core = $twig->getExtension(CoreExtension::class);
        $core->setTimezone(app()->getTimezone());

        // Fire an 'afterCreateTwig' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_CREATE_TWIG)) {
            $this->trigger(self::EVENT_AFTER_CREATE_TWIG, new CreateTwigEvent([
                'templateMode' => TemplateMode::get()->value,
                'twig' => $twig,
            ]));
        }

        return $twig;
    }

    /**
     * Registers a new Twig extension both CP and site templates.
     *
     * @param ExtensionInterface $extension
     */
    public function registerTwigExtension(ExtensionInterface $extension): void
    {
        $this->registerCpTwigExtension($extension);
        $this->registerSiteTwigExtension($extension);
    }

    /**
     * Registers a new Twig extension for CP templates.
     *
     * @param ExtensionInterface $extension
     * @since 5.5.0
     */
    public function registerCpTwigExtension(ExtensionInterface $extension): void
    {
        // Make sure this extension isn't already registered
        $class = get_class($extension);
        if (isset($this->_cpTwigExtensions[$class])) {
            return;
        }

        $this->_cpTwigExtensions[$class] = $extension;

        if (isset($this->_cpTwig)) {
            try {
                $this->_cpTwig->addExtension($extension);
            } catch (LogicException) {
                $this->_cpTwig = null;
            }
        }
    }

    /**
     * Registers a new Twig extension for site templates.
     *
     * @param ExtensionInterface $extension
     * @since 5.5.0
     */
    public function registerSiteTwigExtension(ExtensionInterface $extension): void
    {
        // Make sure this extension isn't already registered
        $class = get_class($extension);
        if (isset($this->_siteTwigExtensions[$class])) {
            return;
        }

        $this->_siteTwigExtensions[$class] = $extension;

        if (isset($this->_siteTwig)) {
            try {
                $this->_siteTwig->addExtension($extension);
            } catch (LogicException) {
                $this->_siteTwig = null;
            }
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
            $templateMode = TemplateMode::get()->value;
        }

        if (!$this->beforeRenderTemplate($template, $variables, $templateMode)) {
            return '';
        }

        Log::debug("Rendering template: $template", [__METHOD__]);

        $oldTemplateMode = TemplateMode::get();
        TemplateMode::set(TemplateMode::from($templateMode));

        // Render and return
        $renderingTemplate = $this->_renderingTemplate;
        $this->_renderingTemplate = $template;

        try {
            $output = $this->getTwig()->render($template, $variables);
        } finally {
            $this->_renderingTemplate = $renderingTemplate;
            TemplateMode::set($oldTemplateMode);
        }

        $this->afterRenderTemplate($template, $variables, $templateMode, $output);
        return $output;
    }

    /**
     * Renders a Twig template in a sandboxed environment.
     *
     * @param string $template The name of the template to load
     * @param array $variables The variables that should be available to the template
     * @param string|null $templateMode The template mode to use
     * @return string the rendering result
     * @throws TwigLoaderError
     * @throws TwigRuntimeError
     * @throws TwigSyntaxError
     * @throws Exception if $templateMode is invalid
     * @see renderTemplate()
     * @since 4.17.0
     */
    public function renderSandboxedTemplate(string $template, array $variables = [], ?string $templateMode = null): string
    {
        return $this->sandbox(fn() => $this->renderTemplate($template, $variables, $templateMode), $templateMode);
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
            $templateMode = TemplateMode::get()->value;
        }

        if (!$this->beforeRenderPageTemplate($template, $variables, $templateMode)) {
            return '';
        }

        ob_start();
        ob_implicit_flush(false);

        $oldTemplateMode = TemplateMode::get();
        TemplateMode::set(TemplateMode::from($templateMode));

        $isRenderingPageTemplate = $this->_isRenderingPageTemplate;
        $this->_isRenderingPageTemplate = true;

        try {
            $this->beginPage();
            echo $this->renderTemplate($template, $variables);
            $this->endPage();
        } finally {
            $this->_isRenderingPageTemplate = $isRenderingPageTemplate;
            TemplateMode::set($oldTemplateMode);
            $output = ob_get_clean();
        }

        $this->afterRenderPageTemplate($template, $variables, $templateMode, $output);
        return $output;
    }

    /**
     * Renders a template defined by a string.
     *
     * @param string $template The source template string.
     * @param array $variables Any variables that should be available to the template.
     * @param string $templateMode The template mode to use.
     * @param bool $escapeHtml Whether dynamic HTML should be escaped
     * @return string The rendered template.
     * @throws TwigLoaderError
     * @throws TwigSyntaxError
     */
    public function renderString(string $template, array $variables = [], string $templateMode = TemplateMode::Site->value, bool $escapeHtml = false): string
    {
        // If there are no dynamic tags, just return the template
        if (!str_contains($template, '{')) {
            return $template;
        }

        $oldTemplateMode = TemplateMode::get();
        TemplateMode::set(TemplateMode::from($templateMode));

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
            TemplateMode::set($oldTemplateMode);
        }
    }

    /**
     * Renders a template defined by a string in a sandboxed environment.
     *
     * @param string $template The source template string.
     * @param array $variables Any variables that should be available to the template.
     * @param string $templateMode The template mode to use.
     * @param bool $escapeHtml Whether dynamic HTML should be escaped
     * @return string The rendered template.
     * @throws TwigLoaderError
     * @throws TwigSyntaxError
     * @see renderString()
     * @since 4.17.0
     */
    public function renderSandboxedString(string $template, array $variables = [], string $templateMode = TemplateMode::Site->value, bool $escapeHtml = false): string
    {
        return $this->sandbox(fn() => $this->renderString($template, $variables, $templateMode, $escapeHtml), $templateMode);
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
    public function renderObjectTemplate(string $template, mixed $object, array $variables = [], string $templateMode = TemplateMode::Site->value): string
    {
        // If there are no dynamic tags, just return the template
        if (!str_contains($template, '{')) {
            return trim($template);
        }

        $oldTemplateMode = TemplateMode::get();
        TemplateMode::set(TemplateMode::from($templateMode));
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
            if ($object instanceof Arrayable) {
                if (preg_match('/\binclude\b/', $template)) {
                    // Export all normal fields, since we don’t know what the included template is going to need
                    // (https://github.com/craftcms/cms/issues/18165)
                    $fields = [];
                } else {
                    $fields = $this->filterFieldsByTemplate($object->fields(), $template) ?: ['!'];
                }

                $variables += $object->toArray(
                    $fields,
                    $this->filterFieldsByTemplate($object->extraFields(), $template),
                    false,
                );
            }

            if ($object instanceof Model) {
                foreach ($object->attributes() as $name) {
                    if (
                        !isset($variables[$name]) &&
                        preg_match(sprintf('/\b%s\b/', preg_quote($name, '/')), $template)
                    ) {
                        $variables[$name] = $object->$name;
                    }
                }
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
            TemplateMode::set($oldTemplateMode);

            // Re-enable strict variables
            if ($strictVariables) {
                $twig->enableStrictVariables();
            }
        }
    }

    private function filterFieldsByTemplate(array $fields, string $template): array
    {
        $filtered = [];

        foreach ($fields as $field => $definition) {
            if (is_int($field)) {
                $field = $definition;
            }
            if (preg_match(sprintf('/\b%s\b/', preg_quote($field, '/')), $template)) {
                $filtered[] = $field;
            }
        }

        return $filtered;
    }

    /**
     * Renders an object template in a sandboxed environment.
     *
     * @param string $template the source template string
     * @param mixed $object the object that should be passed into the template
     * @param array $variables any additional variables that should be available to the template
     * @param string $templateMode The template mode to use.
     * @return string The rendered template.
     * @throws Exception in case of failure
     * @throws Throwable in case of failure
     * @see renderObjectTemplate()
     * @since 4.17.0
     */
    public function renderSandboxedObjectTemplate(
        string $template,
        mixed $object,
        array $variables = [],
        string $templateMode = TemplateMode::Site->value,
    ): string {
        return $this->sandbox(fn() => $this->renderObjectTemplate($template, $object, $variables, $templateMode), $templateMode);
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
                $token = 'tok_' . Str::random(10);
                $tokens[$token] = $matches[0];
                return $token;
            },
            $template
        );

        // Tokenize any remaining Twig tags (including print tags)
        $template = preg_replace_callback('/\{%-?\s*\w+.*?%\}|(?<!\{)\{\{(?!\{).+?(?<!\})\}\}(?!\})/s',
            function(array $matches) use (&$tokens) {
                $token = 'tok_' . Str::random(10);
                $tokens[$token] = $matches[0];
                return $token;
            },
            $template
        );

        // Tokenize inline code and code blocks
        $template = preg_replace_callback('/(?<!`)(`|`{3,})(?!`).*?(?<!`)\1(?!`)/s', function(array $matches) use (&$tokens) {
            $token = 'tok_' . Str::random(10);
            $tokens[$token] = '{% verbatim %}' . $matches[0] . '{% endverbatim %}';
            return $token;
        }, $template);

        // Tokenize objects (call preg_replace_callback() multiple times in case there are nested objects)
        while (true) {
            $template = preg_replace_callback('/\{\s*([\'"]?)\w+\1\s*:[^\{]+?\}/', function(array $matches) use (&$tokens) {
                $token = 'tok_' . Str::random(10);
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
     * @param string $name The name of the template.
     * @param string|null $templateMode The template mode to use.
     * @param bool $publicOnly Whether to only look for public templates (template paths that don’t start with the private template trigger).
     * @return string|false The path to the template if it exists, or `false`.
     * @throws TwigLoaderError
     */
    public function resolveTemplate(string $name, ?string $templateMode = null, bool $publicOnly = false): string|false
    {
        if ($templateMode !== null) {
            $oldTemplateMode = TemplateMode::get();
            TemplateMode::set(TemplateMode::from($templateMode));
        }

        try {
            return $this->_resolveTemplateInternal($name, $publicOnly);
        } finally {
            if (isset($oldTemplateMode)) {
                TemplateMode::set($oldTemplateMode);
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
        $name = trim(preg_replace('#/{2,}#', '/', str_replace('\\', '/', Str::convertToUtf8($name))), '/');

        $key = TemplateMode::get()->templatesPath() . ':' . $name;

        // Is this template path already cached?
        if (isset($this->_templatePaths[$key])) {
            return $this->_templatePaths[$key];
        }

        // Validate the template name
        $this->_validateTemplateName($name);

        // Look for the template in the main templates folder
        $basePaths = [];

        // Should we be looking for a localized version of the template?
        if (TemplateMode::is(TemplateMode::Site) && Cms::isInstalled()) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $sitePath = TemplateMode::get()->templatesPath() . DIRECTORY_SEPARATOR . Sites::getCurrentSite()->handle;
            if (is_dir($sitePath)) {
                $basePaths[] = $sitePath;
            }
        }

        $basePaths[] = TemplateMode::get()->templatesPath();

        foreach ($basePaths as $basePath) {
            if (($path = $this->_resolveTemplate($basePath, $name, $publicOnly)) !== null) {
                return $this->_templatePaths[$key] = $path;
            }
        }

        unset($basePaths);

        // Check any registered template roots
        $roots = TemplateMode::get()->templateRoots();

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
     * @deprecated 6.0.0 use {@see TemplateMode::templateRoots()} instead.
     */
    public function getCpTemplateRoots(): array
    {
        return TemplateMode::Cp->templateRoots();
    }

    /**
     * Returns any registered site template roots.
     *
     * @return array
     * @deprecated 6.0.0 use {@see TemplateMode::templateRoots()} instead.
     */
    public function getSiteTemplateRoots(): array
    {
        return TemplateMode::Site->templateRoots();
    }

    /**
     * @inheritdoc
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::js()} instead.
     */
    public function registerJs($js, $position = self::POS_READY, $key = null): void
    {
        // Trim any whitespace and ensure it ends with a semicolon.
        $js = Str::finish(trim($js, " \t\n\r\0\x0B"), ';');
        $key = $key ?: md5($js);

        match ($position) {
            self::POS_HEAD => (function() use ($js, $key, $position) {
                $this->registry()->js($js, Position::Head, $key);
                $this->_jsOriginalPositions[$key] = $position;
            })(),
            self::POS_BEGIN => (function() use ($js, $key, $position) {
                $this->_beginJs[$key] = $js;
                $this->_jsOriginalPositions[$key] = $position;
            })(),
            self::POS_END => (function() use ($js, $key, $position) {
                $this->registry()->js($js, Position::Body, $key);
                $this->_jsOriginalPositions[$key] = $position;
            }
            )(),
            self::POS_READY => $this->_readyJs[$key] = $js,
            self::POS_LOAD => $this->_loadJs[$key] = $js,
            default => null,
        };
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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::jsWithVars()} instead.
     */
    public function registerJsWithVars(callable $jsFn, array $vars, int $position = self::POS_READY, ?string $key = null): void
    {
        $jsVars = array_map(fn($variable) => Json::encode($variable), $vars);
        $js = call_user_func($jsFn, ...array_values($jsVars));
        $this->registerJs($js, $position, $key);
    }

    /**
     * Starts a buffer for any JavaScript code registered with [[registerJs()]].
     *
     * The buffer’s contents can be cleared and returned later via [[clearJsBuffer()]].
     *
     * @see clearJsBuffer()
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::startJsBuffer()} instead.
     */
    public function startJsBuffer(): void
    {
        $this->registry()->startJsBuffer();
        $this->_jsBufferDepth++;

        $this->_readyLoadBuffers[] = [
            'ready' => $this->_readyJs,
            'load' => $this->_loadJs,
            'begin' => $this->_beginJs,
        ];
        $this->_readyJs = [];
        $this->_loadJs = [];
        $this->_beginJs = [];

        $this->_jsOriginalPositionBuffers[] = $this->_jsOriginalPositions;
        $this->_jsOriginalPositions = [];
    }

    /**
     * Clears and ends a buffer started via [[startJsBuffer()]], returning any JavaScript code that was registered while
     * the buffer was active.
     *
     * @param bool $scriptTag Whether the returned JavaScript code should be wrapped in a `<script>` tag.
     * @param bool $combine Whether the JavaScript code should be returned in a combined blob. (Position and key info will be lost.)
     *
     * @return string|array|false The JavaScript code that was registered while the buffer was active, or `false` if there wasn't an active buffer.
     * @see startJsBuffer()
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::clearJsBuffer()} instead.
     */
    public function clearJsBuffer(bool $scriptTag = true, bool $combine = true): string|array|false
    {
        if ($this->_jsBufferDepth === 0) {
            return false;
        }

        // Capture what was registered during the buffer and restore pre-buffer state
        $registryState = $this->registry()->clearJsBuffer(scriptTag: false, combine: false);
        $this->_jsBufferDepth--;

        // Capture and restore the adapter's ready/load/begin JS
        $bufferedReadyJs = $this->_readyJs;
        $bufferedLoadJs = $this->_loadJs;
        $bufferedBeginJs = $this->_beginJs;

        if (!empty($this->_readyLoadBuffers)) {
            $previousReadyLoad = array_pop($this->_readyLoadBuffers);
            $this->_readyJs = $previousReadyLoad['ready'];
            $this->_loadJs = $previousReadyLoad['load'];
            $this->_beginJs = $previousReadyLoad['begin'];
        } else {
            $this->_readyJs = [];
            $this->_loadJs = [];
            $this->_beginJs = [];
        }

        // Capture and restore the original position map
        $bufferedPositions = $this->_jsOriginalPositions;
        $this->_jsOriginalPositions = array_pop($this->_jsOriginalPositionBuffers) ?? [];

        // Build the buffered JS array in the Yii2 position format
        $bufferedJs = [];

        // Split Position::Head entries back to their original Yii2 position
        if (!empty($registryState[Position::Head->value])) {
            foreach ($registryState[Position::Head->value] as $key => $js) {
                $originalPos = $bufferedPositions[$key] ?? self::POS_HEAD;
                $bufferedJs[$originalPos][$key] = $js;
            }
        }
        // Position::Body entries map back to POS_END
        if (!empty($registryState[Position::Body->value])) {
            foreach ($registryState[Position::Body->value] as $key => $js) {
                $originalPos = $bufferedPositions[$key] ?? self::POS_END;
                $bufferedJs[$originalPos][$key] = $js;
            }
        }
        // POS_BEGIN entries come from the adapter-local _beginJs array
        if (!empty($bufferedBeginJs)) {
            $bufferedJs[self::POS_BEGIN] = $bufferedBeginJs;
        }
        if (!empty($bufferedReadyJs)) {
            $bufferedJs[self::POS_READY] = $bufferedReadyJs;
        }
        if (!empty($bufferedLoadJs)) {
            $bufferedJs[self::POS_LOAD] = $bufferedLoadJs;
        }

        if ($combine) {
            $js = '';

            foreach ([self::POS_HEAD, self::POS_BEGIN, self::POS_END, self::POS_LOAD, self::POS_READY] as $pos) {
                if (!empty($bufferedJs[$pos])) {
                    $js .= implode("\n", $bufferedJs[$pos]) . "\n";
                }
            }

            if ($scriptTag && !empty($js)) {
                return Html::script($js, ['type' => 'text/javascript'])->render();
            }

            return $js;
        }

        if ($scriptTag) {
            foreach ($bufferedJs as $pos => $js) {
                $bufferedJs[$pos] = Html::script(implode("\n", $js), ['type' => 'text/javascript'])->render();
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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::startScriptBuffer()} instead.
     */
    public function startScriptBuffer(): void
    {
        $this->registry()->startScriptBuffer();
        $this->_scriptBufferDepth++;

        $this->_scriptBeginBuffers[] = $this->_beginScripts;
        $this->_beginScripts = [];
    }

    /**
     * Clears and ends a buffer started via [[startScriptBuffer()]], returning any `<script>` tags that were registered
     * while the buffer was active.
     *
     * @return array|false The `<script>` tags that were registered while the buffer was active, or `false` if there wasn't an active buffer.
     * @see startScriptBuffer()
     * @since 3.7.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::clearScriptBuffer()} instead.
     */
    public function clearScriptBuffer(): array|false
    {
        if ($this->_scriptBufferDepth === 0) {
            return false;
        }

        // Capture what was registered during the buffer and restore pre-buffer state
        $registryState = $this->registry()->clearScriptBuffer();
        $this->_scriptBufferDepth--;

        $bufferedBeginScripts = $this->_beginScripts;
        $this->_beginScripts = array_pop($this->_scriptBeginBuffers) ?? [];

        // Map registry positions back to Yii2 positions
        $bufferedScripts = [];
        if (!empty($registryState[Position::Head->value])) {
            $bufferedScripts[self::POS_HEAD] = array_map(fn($v) => (string) $v, $registryState[Position::Head->value]);
        }
        if (!empty($bufferedBeginScripts)) {
            $bufferedScripts[self::POS_BEGIN] = array_map(fn($v) => (string) $v, $bufferedBeginScripts);
        }
        if (!empty($registryState[Position::Body->value])) {
            $bufferedScripts[self::POS_END] = array_map(fn($v) => (string) $v, $registryState[Position::Body->value]);
        }

        return $bufferedScripts;
    }

    /**
     * Starts a buffer for any `<style>` tags registered with [[registerCss()]].
     *
     * The buffer’s contents can be cleared and returned later via [[clearCssBuffer()]].
     *
     * @see clearCssBuffer()
     * @since 3.7.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::startCssBuffer()} instead.
     */
    public function startCssBuffer(): void
    {
        $this->registry()->startCssBuffer();
        $this->_cssBufferDepth++;
    }

    /**
     * Clears and ends a buffer started via [[startCssBuffer()]], returning any `<style>` tags that were registered
     * while the buffer was active.
     *
     * @return array|false The `<style>` tags that were registered while the buffer was active, or `false` if there wasn't an active buffer.
     * @see startCssBuffer()
     * @since 3.7.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::clearCssBuffer()} instead.
     */
    public function clearCssBuffer(): array|false
    {
        if ($this->_cssBufferDepth === 0) {
            return false;
        }

        $registryState = $this->registry()->clearCssBuffer();
        $this->_cssBufferDepth--;

        return array_map(fn($v) => (string) $v, $registryState);
    }

    /**
     * Starts a buffer for any `<link>` tags registered with [[registerCssFile()]].
     *
     * The buffer’s contents can be cleared and returned later via [[clearCssFileBuffer()]].
     *
     * @see clearCssFileBuffer()
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::startCssFileBuffer()} instead.
     */
    public function startCssFileBuffer(): void
    {
        $this->registry()->startCssFileBuffer();
        $this->_cssFileBufferDepth++;
    }

    /**
     * Clears and ends a buffer started via [[startCssFileBuffer()]], returning any `<link rel="stylesheet">` tags that were registered
     * while the buffer was active.
     *
     * @return array|false The `<link rel="stylesheet">` tags that were registered while the buffer was active, or `false` if there wasn't an active buffer.
     * @see startCssFileBuffer()
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::clearCssFileBuffer()} instead.
     */
    public function clearCssFileBuffer(): array|false
    {
        if ($this->_cssFileBufferDepth === 0) {
            return false;
        }

        $registryState = $this->registry()->clearCssFileBuffer();
        $this->_cssFileBufferDepth--;

        return $registryState;
    }

    /**
     * Starts a buffer for any `<script>` tags registered with [[registerJsFile()]].
     *
     * The buffer’s contents can be cleared and returned later via [[clearJsFileBuffer()]].
     *
     * @see clearJsFileBuffer()
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::startJsFileBuffer()} instead.
     */
    public function startJsFileBuffer(): void
    {
        $this->registry()->startJsFileBuffer();
        $this->_jsFileBufferDepth++;

        $this->_jsFileBeginBuffers[] = $this->_beginJsFiles;
        $this->_beginJsFiles = [];
    }

    /**
     * Clears and ends a buffer started via [[startJsFileBuffer()]], returning any `<script>` tags that were registered
     * while the buffer was active.
     *
     * @return array|false The `<script>` tags that were registered while the buffer was active (indexed by position), or `false` if there wasn't an active buffer.
     * @see startJsFileBuffer()
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::clearJsFileBuffer()} instead.
     */
    public function clearJsFileBuffer(): array|false
    {
        if ($this->_jsFileBufferDepth === 0) {
            return false;
        }

        $registryState = $this->registry()->clearJsFileBuffer();
        $this->_jsFileBufferDepth--;

        $bufferedBeginJsFiles = $this->_beginJsFiles;
        $this->_beginJsFiles = array_pop($this->_jsFileBeginBuffers) ?? [];

        // Map registry positions back to Yii2 positions
        $bufferedJsFiles = [];
        if (!empty($registryState[Position::Head->value])) {
            $bufferedJsFiles[self::POS_HEAD] = $registryState[Position::Head->value];
        }
        if (!empty($bufferedBeginJsFiles)) {
            $bufferedJsFiles[self::POS_BEGIN] = $bufferedBeginJsFiles;
        }
        if (!empty($registryState[Position::Body->value])) {
            $bufferedJsFiles[self::POS_END] = $registryState[Position::Body->value];
        }

        foreach ($bufferedJsFiles as $files) {
            foreach (array_keys($files) as $key) {
                $hash = $this->resourceHash($key);
                unset($this->_registeredJsFiles[$hash]);
            }
        }

        return $bufferedJsFiles;
    }

    /**
     * Starts a buffer for any html tags registered with [[registerHtml()]].
     *
     * @since 4.3.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::startHtmlBuffer()} instead.
     */
    public function startHtmlBuffer(): void
    {
        $this->registry()->startHtmlBuffer();
        $this->_htmlBufferDepth++;

        $this->_htmlBeginBuffers[] = $this->_beginHtml;
        $this->_beginHtml = [];
    }

    /**
     * Clears and ends a buffer started via [[startHtmlBuffer()]], returning any html tags that were registered
     * while the buffer was active.
     *
     * @return array|false The html that was registered while the buffer was active or `false` if there wasn't an active buffer.
     * @since 4.3.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::clearHtmlBuffer()} instead.
     */
    public function clearHtmlBuffer(): array|false
    {
        if ($this->_htmlBufferDepth === 0) {
            return false;
        }

        $registryState = $this->registry()->clearHtmlBuffer();
        $this->_htmlBufferDepth--;

        $bufferedBeginHtml = $this->_beginHtml;
        $this->_beginHtml = array_pop($this->_htmlBeginBuffers) ?? [];

        // Map registry positions back to Yii2 positions
        $bufferedHtml = [];
        if (!empty($registryState[Position::Head->value])) {
            $bufferedHtml[self::POS_HEAD] = $registryState[Position::Head->value];
        }
        if (!empty($bufferedBeginHtml)) {
            $bufferedHtml[self::POS_BEGIN] = $bufferedBeginHtml;
        }
        if (!empty($registryState[Position::Body->value])) {
            $bufferedHtml[self::POS_END] = $registryState[Position::Body->value];
        }

        return $bufferedHtml;
    }

    /**
     * Starts a buffer for any `<meta>` tags registered with [[registerMetaTag()]].
     *
     * The buffer’s contents can be cleared and returned later via [[clearMetaTagBuffer()]].
     *
     * @see clearMetaTagBuffer()
     * @since 4.5.8
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::startMetaTagBuffer()} instead.
     */
    public function startMetaTagBuffer(): void
    {
        $this->registry()->startMetaTagBuffer();
        $this->_metaTagBufferDepth++;
    }

    /**
     * Clears and ends a buffer started via [[startMetaTagBuffer()]], returning any `<meta>` tags that were registered
     * while the buffer was active.
     *
     * @return array|false The `<meta>` tags that were registered while the buffer was active (indexed by position), or `false` if there wasn't an active buffer.
     * @see startMetaTagBuffer()
     * @since 4.5.8
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::clearMetaTagBuffer()} instead.
     */
    public function clearMetaTagBuffer(): array|false
    {
        if ($this->_metaTagBufferDepth === 0) {
            return false;
        }

        $registryState = $this->registry()->clearMetaTagBuffer();
        $this->_metaTagBufferDepth--;

        return $registryState;
    }

    /**
     * Starts a buffer for any asset bundles registered with [[registerAssetBundle()]].
     *
     * The buffer’s contents can be cleared and returned later via [[clearAssetBundleBuffer()]].
     *
     * @see clearAssetBundleBuffer()
     * @since 5.3.0
     * @deprecated 6.0.0. AssetBundle support is deprecated
     */
    public function startAssetBundleBuffer(): void
    {
        $this->_assetBundleBuffers[] = $this->assetBundles;
        $this->assetBundles = [];
    }

    /**
     * Clears and ends a buffer started via [[startAssetBundleBuffer()]], returning any asset bundles that were registered
     * while the buffer was active.
     *
     * @return array|false The asset bundles that were registered while the buffer was active, or `false` if there wasn’t an active buffer.
     * @see startAssetBundleBuffer()
     * @since 5.3.0
     * @deprecated 6.0.0. AssetBundle support is deprecated
     */
    public function clearAssetBundleBuffer(): array|false
    {
        if (empty($this->_assetBundleBuffers)) {
            return false;
        }

        $bufferedAssetBundles = $this->assetBundles;
        $this->assetBundles = array_pop($this->_assetBundleBuffers);
        return $bufferedAssetBundles;
    }

    /**
     * Starts a buffer for any JavaScript imports registered with [[registerJsImport()]].
     *
     * The buffer’s contents can be cleared and returned later via [[clearJsImportBuffer()]].
     *
     * @see clearJsImportBuffer()
     * @since 5.6.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::startJsImportBuffer()} instead.
     */
    public function startJsImportBuffer(): void
    {
        $this->registry()->startJsImportBuffer();
        $this->_jsImportBufferDepth++;
    }

    /**
     * @inheritdoc
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::clearJsImportBuffer()} instead.
     */
    public function clearJsImportBuffer(): array|false
    {
        if ($this->_jsImportBufferDepth === 0) {
            return false;
        }

        $registryState = $this->registry()->clearJsImportBuffer();
        $this->_jsImportBufferDepth--;

        return $registryState;
    }

    /**
     * @inheritdoc
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::jsFile()} instead.
     */
    public function registerJsFile($url, $options = [], $key = null): void
    {
        // If the file lives within cpresources/, ignore it because it came from an asset bundle
        if (!str_starts_with($url, $this->assetManager->baseUrl)) {
            $hash = $this->resourceHash($key ?: $url);
            if (isset($this->_registeredJsFiles[$hash])) {
                return;
            }
            $this->_registeredJsFiles[$hash] = true;
        }

        // Map Yii2 position to registry position
        $position = (int) ($options['position'] ?? self::POS_END);
        unset($options['position']);

        $key ??= $url;

        if ($position === self::POS_BEGIN) {
            $this->_beginJsFiles[$key] = Html::javaScriptFile($url, $options)->render();
            return;
        }

        $registryPosition = match ($position) {
            self::POS_HEAD => Position::Head->value,
            default => Position::Body->value,
        };
        $options['position'] = $registryPosition;

        $this->registry()->jsFile($url, $options, $key);
    }

    /**
     * @inheritdoc
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::metaTag()} instead.
     */
    public function registerMetaTag($options, $key = null): void
    {
        $this->registry()->metaTag($options, $key);
    }

    /**
     * @inheritdoc
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::linkTag()} instead.
     */
    public function registerLinkTag($options, $key = null): void
    {
        $this->registry()->linkTag($options, $key);
    }

    /**
     * @inheritdoc
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::css()} instead.
     */
    public function registerCss($css, $options = [], $key = null): void
    {
        $this->registry()->css($css, $options, $key);
    }

    /**
     * @inheritdoc
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::cssFile()} instead.
     */
    public function registerCssFile($url, $options = [], $key = null): void
    {
        $this->registry()->cssFile($url, $options, $key);
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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::script()} instead.
     */
    public function registerScript(string $script, int $position = self::POS_END, array $options = [], ?string $key = null): void
    {
        $key ??= md5($script);

        if ($position === self::POS_BEGIN) {
            $this->_beginScripts[$key] = Html::script($script, $options)->render();
            return;
        }

        $registryPosition = match ($position) {
            self::POS_HEAD => Position::Head,
            default => Position::Body,
        };

        $this->registry()->script($script, $registryPosition, $options, $key);
    }

    /**
     * Registers a generic `<script>` tag with the given variables, pre-JSON-encoded.
     *
     * @param callable $scriptFn callback function that returns the JS code to be registered.
     * @param array $vars Array of variables that will be JSON-encoded before being passed to `$scriptFn`
     * @param int $position the position at which the JS script tag should be inserted
     *  in a page. The possible values are:
     *  - [[POS_HEAD]]: in the head section
     *  - [[POS_BEGIN]]: at the beginning of the body section
     *  - [[POS_END]]: at the end of the body section
     * @param array $options the HTML attributes for the `<script>` tag.
     * @param string|null $key the key that identifies the generic `<script>` code block. If null, it will use
     * $script as the key. If two generic `<script>` code blocks are registered with the same key, the latter
     * will overwrite the former.
     * @since 5.6.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::scriptWithVars()} instead.
     */
    public function registerScriptWithVars(callable $scriptFn, array $vars, int $position = self::POS_END, array $options = [], ?string $key = null): void
    {
        $jsVars = array_map(fn($variable) => Json::encode($variable), $vars);

        $script = call_user_func($scriptFn, ...array_values($jsVars));
        $this->registerScript($script, $position, $options);
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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::html()} instead.
     */
    public function registerHtml(string $html, int $position = self::POS_END, ?string $key = null): void
    {
        $key ??= md5($html);

        if ($position === self::POS_BEGIN) {
            $this->_beginHtml[$key] = $html;
            return;
        }

        $registryPosition = match ($position) {
            self::POS_HEAD => Position::Head,
            default => Position::Body,
        };

        $this->registry()->html($html, $registryPosition, $key);
    }

    /**
     * Registers a JavaScript import map entry to be injected into the final page response.
     *
     * @param string $key The module specifier.
     * @param string $value  The URL or path to the resource the key will resolve to.
     * @since 5.6.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::jsImport()} instead.
    */
    public function registerJsImport(string $key, string $value): void
    {
        $this->registry()->jsImport($key, $value);
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

        $html = $this->registry()->headHtml(clear: $clear);

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

        // Include both body-begin and body-end content
        $html = $this->renderBodyBeginHtml() . $this->renderBodyEndHtml(true);

        // Clear out the queued up files
        if ($clear === true) {
            // Clear registry body content (head was not touched)
            $this->registry()->bodyHtml(clear: true);

            // Clear adapter's internal begin/ready/load JS
            $this->_beginJs = [];
            $this->_beginHtml = [];
            $this->_beginScripts = [];
            $this->_beginJsFiles = [];
            $this->_readyJs = [];
            $this->_loadJs = [];
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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::translations()} instead.
     */
    public function registerTranslations(string $category, array $messages): void
    {
        $jsCategory = Json::encode($category);
        $js = '';

        foreach ($messages as $message) {
            $translation = t($message, category: $category);

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
     * Registers icons for `Craft.ui.icon()`.
     *
     * @param string[] $icons The icons to be registered
     * @since 5.7.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\AssetRegistry::icons()} instead.
     */
    public function registerIcons(array $icons): void
    {
        $this->registry()->icons($icons);
    }

    /**
     * Returns the active namespace.
     *
     * This is the default namespaces that will be used when [[namespaceInputs()]], [[namespaceInputName()]],
     * and [[namespaceInputId()]] are called, if their $namespace arguments are null.
     *
     * @return string|null The namespace.
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\InputNamespace::get()} instead.
     */
    public function getNamespace(): ?string
    {
        return InputNamespace::get();
    }

    /**
     * Sets the active namespace.
     *
     * This is the default namespaces that will be used when [[namespaceInputs()]], [[namespaceInputName()]],
     * and [[namespaceInputId()]] are called, if their|null $namespace arguments are null.
     *
     * @param string|null $namespace The new namespace. Set to null to remove the namespace.
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\InputNamespace::set()} instead.
     */
    public function setNamespace(?string $namespace): void
    {
        InputNamespace::set($namespace);
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
     * @param bool $forceModified Whether the name should be considered modified regardless of the initial form value
     * @since 3.4.0
     */
    public function registerDeltaName(string $inputName, bool $forceModified = false): void
    {
        if ($this->_registerDeltaNames) {
            $inputName = InputNamespace::namespaceInputName($inputName);
            $this->_deltaNames[] = $inputName;

            if ($forceModified) {
                $this->_modifiedDeltaNames[] = $inputName;
            }
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
            $this->_initialDeltaValues[InputNamespace::namespaceInputName($inputName)] = $value;
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
     * Returns all the registered delta input names.
     *
     * @return string[]
     * @see registerDeltaName()
     * @since 3.4.0
     */
    public function getDeltaNames(): array
    {
        return $this->_deltaNames;
    }

    /**
     * Returns all the registered delta input names that should be considered modified.
     *
     * @return string[]
     * @see registerDeltaName()
     * @since 5.2.1
     */
    public function getModifiedDeltaNames(): array
    {
        return $this->_modifiedDeltaNames;
    }

    /**
     * Returns the current template mode (either `site` or `cp`).
     *
     * @return string Either `site` or `cp`.
     * @deprecated 6.0.0 use {@see TemplateMode::get()} instead.
     */
    public function getTemplateMode(): string
    {
        return TemplateMode::get()->value;
    }

    /**
     * Sets the current template mode.
     *
     * The template mode defines:
     * - the base path that templates should be looked for in
     * - the default template file extensions that should be automatically added when looking for templates
     * - the "index" template filenames that should be checked when looking for templates
     *
     * @param string|TemplateMode $templateMode Either 'site' or 'cp'
     * @throws Exception if $templateMode is invalid
     * @deprecated 6.0.0 use {@see TemplateMode::set()} instead.
     */
    public function setTemplateMode(string|TemplateMode $templateMode): void
    {
        $templateMode = is_string($templateMode)
            ? TemplateMode::from($templateMode)
            : $templateMode;

        // Set the new template mode
        TemplateMode::set($templateMode);
    }

    /**
     * Returns the base path that templates should be found in.
     *
     * @return string
     * @deprecated 6.0.0 use {@see TemplateMode::templatesPath()} instead.
     */
    public function getTemplatesPath(): string
    {
        return TemplateMode::get()->templatesPath();
    }

    /**
     * Sets the base path that templates should be found in.
     *
     * @param string $templatesPath
     *
     * @deprecated 6.0.0 use {@see TemplateMode::templatesPath()} instead.
     */
    public function setTemplatesPath(string $templatesPath): void
    {
        // Noop
    }

    /**
     * Renames HTML input names so they belong to a namespace.
     *
     * This method will go through the passed-in $html looking for `name=` attributes, and renaming their values such
     * that they will live within the passed-in $namespace (or the [[getNamespace()|active namespace]]).
     * By default, any `id=`, `for=`, `list=`, `data-target=`, `data-reverse-target=`, and `data-target-prefix=`
     * attributes will get namespaced as well, by prepending the namespace and a hyphens to their values.
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
     *
     * @return string The HTML with namespaced attributes
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\InputNamespace::namespaceInputs()} instead.
     */
    public function namespaceInputs(callable|string $html, ?string $namespace = null, bool $otherAttributes = true, bool $withClasses = false): string
    {
        return InputNamespace::namespaceInputs($html, $namespace, $otherAttributes, $withClasses);
    }

    /**
     * Namespaces an input name.
     *
     * This method applies the same namespacing treatment that [[namespaceInputs()]] does to `name=` attributes,
     * but only to a single value, which is passed directly into this method.
     *
     * @param string $inputName The input name that should be namespaced.
     * @param string|null $namespace The namespace. Defaults to the [[getNamespace()|active namespace]].
     *
     * @return string The namespaced input name.
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\InputNamespace::namespaceInputName()} instead.
     */
    public function namespaceInputName(string $inputName, ?string $namespace = null): string
    {
        return InputNamespace::namespaceInputName($inputName, $namespace);
    }

    /**
     * Namespaces an input ID.
     *
     * This method applies the same namespacing treatment that [[namespaceInputs()]] does to `id=` attributes,
     * but only to a single value, which is passed directly into this method.
     *
     * @param string $inputId The input ID that should be namespaced.
     * @param string|null $namespace The namespace. Defaults to the [[getNamespace()|active namespace]].
     *
     * @return string The namespaced input ID.
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\InputNamespace::namespaceId()} instead.
     */
    public function namespaceInputId(string $inputId, ?string $namespace = null): string
    {
        return InputNamespace::namespaceId($inputId, $namespace);
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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\TemplateHooks::register()} instead.
     */
    public function hook(string $hook, callable $method, bool $append = true): void
    {
        $this->templateHooks()->register($hook, $method, $append);
    }

    /**
     * Invokes a template hook.
     *
     * This is called by [[HookNode|`{% hook %}` tags]].
     *
     * @param string $hook The hook name.
     * @param array $context The current template context.
     * @return string Whatever the hooks returned.
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\TemplateHooks::invoke()} instead.
     */
    public function invokeHook(string $hook, array &$context): string
    {
        return $this->templateHooks()->invoke($hook, $context);
    }

    /**
     * Sets the JS files that should be marked as already registered.
     *
     * @param string[] $keys
     *
     * @since 3.0.10
     * @deprecated 6.0.0
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
     * @deprecated 6.0.0
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
        if (!$ajaxMode && TemplateMode::is(TemplateMode::Cp)) {
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
    public function beforeRenderTemplate(string &$template, array &$variables, string &$templateMode): bool
    {
        // Fire a 'beforeRenderTemplate' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_RENDER_TEMPLATE)) {
            $event = new TemplateEvent([
                'template' => $template,
                'variables' => $variables,
                'templateMode' => $templateMode,
            ]);
            $this->trigger(self::EVENT_BEFORE_RENDER_TEMPLATE, $event);
            $template = $event->template;
            $variables = $event->variables;
            $templateMode = $event->templateMode;
            return $event->isValid;
        }

        return true;
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
    public function beforeRenderPageTemplate(string &$template, array &$variables, string &$templateMode): bool
    {
        // Fire a 'beforeRenderPageTemplate' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_RENDER_PAGE_TEMPLATE)) {
            $event = new TemplateEvent([
                'template' => $template,
                'variables' => $variables,
                'templateMode' => $templateMode,
            ]);
            $this->trigger(self::EVENT_BEFORE_RENDER_PAGE_TEMPLATE, $event);
            $template = $event->template;
            $variables = $event->variables;
            $templateMode = $event->templateMode;
            return $event->isValid;
        }

        return true;
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

    private function sandbox(callable $callback, ?string $templateMode): string
    {
        if (!Craft::$app->getConfig()->getGeneral()->enableTwigSandbox) {
            return $callback();
        }

        $extension = $this->getTwig($templateMode)->getExtension(SandboxExtension::class);

        if ($extension->isSandboxed()) {
            return $callback();
        }

        $extension->enableSandbox();
        try {
            return $callback();
        } finally {
            $extension->disableSandbox();
        }
    }

    /**
     * @inheritdoc
     */
    protected function renderHeadHtml(): string
    {
        return $this->registry()->headHtml(clear: false);
    }

    /**
     * @inheritdoc
     */
    protected function renderBodyBeginHtml(): string
    {
        $lines = [];

        if (!empty($this->_beginScripts)) {
            $lines[] = implode("\n", $this->_beginScripts);
        }

        if (!empty($this->_beginHtml)) {
            $lines[] = implode("\n", $this->_beginHtml);
        }

        if (!empty($this->_beginJsFiles)) {
            $lines[] = implode("\n", $this->_beginJsFiles);
        }

        if (!empty($this->_beginJs)) {
            $lines[] = Html::script(implode("\n", $this->_beginJs))->render();
        }

        return empty($lines) ? '' : implode("\n", $lines);
    }

    /**
     * @inheritdoc
     */
    protected function renderBodyEndHtml($ajaxMode): string
    {
        $html = $this->registry()->bodyHtml(clear: false);

        // Append POS_READY/POS_LOAD JS (kept in adapter, not registry)
        if ($ajaxMode) {
            $scripts = [];
            if (!empty($this->_readyJs)) {
                $scripts[] = implode("\n", $this->_readyJs);
            }
            if (!empty($this->_loadJs)) {
                $scripts[] = implode("\n", $this->_loadJs);
            }
            if (!empty($scripts)) {
                $html .= ($html !== '' ? "\n" : '') . Html::script(implode("\n", $scripts))->render();
            }
        } else {
            if (!empty($this->_readyJs)) {
                $js = "jQuery(function ($) {\n" . implode("\n", $this->_readyJs) . "\n});";
                $html .= ($html !== '' ? "\n" : '') . Html::script($js)->render();
            }
            if (!empty($this->_loadJs)) {
                $js = "jQuery(window).on('load', function () {\n" . implode("\n", $this->_loadJs) . "\n});";
                $html .= ($html !== '' ? "\n" : '') . Html::script($js)->render();
            }
        }

        return $html;
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
        $hash = $this->resourceHash($name);
        if (isset($this->_registeredAssetBundles[$hash])) {
            return;
        }
        $this->_registeredAssetBundles[$hash] = true;
        parent::registerAssetFiles($name);
    }

    /**
     * @inheritdoc
     */
    public function registerAssetBundle($name, $position = null)
    {
        $bundle = parent::registerAssetBundle($name, $position);

        if ($this->hasEventHandlers(self::EVENT_AFTER_REGISTER_ASSET_BUNDLE)) {
            $this->trigger(self::EVENT_AFTER_REGISTER_ASSET_BUNDLE, new AssetBundleEvent([
                'bundleName' => $name,
                'position' => $position,
                'bundle' => $bundle,
            ]));
        }

        return $bundle;
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
        if (str_contains($name, "\0")) {
            throw new TwigLoaderError(t('A template name cannot contain NUL bytes.'));
        }

        if (Path::ensurePathIsContained($name) === false) {
            Log::info('Someone tried to load a template outside the templates folder: ' . $name);
            throw new TwigLoaderError(t('Looks like you are trying to load a template outside the template folder.'));
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
            if ($publicOnly && preg_match(sprintf('/(^|\/)%s/', preg_quote(TemplateMode::get()->privateTemplateTrigger(), '/')), $name)) {
                return null;
            }

            // Maybe $name is already the full file path
            $testPath = $basePath . DIRECTORY_SEPARATOR . $name;

            if (is_file($testPath)) {
                return $testPath;
            }

            foreach (TemplateMode::get()->defaultTemplateExtensions() as $extension) {
                $testPath = $basePath . DIRECTORY_SEPARATOR . $name . '.' . $extension;

                if (is_file($testPath)) {
                    return $testPath;
                }
            }
        }

        foreach (TemplateMode::get()->indexTemplateFilenames() as $filename) {
            foreach (TemplateMode::get()->defaultTemplateExtensions() as $extension) {
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

        $generalConfig = Cms::config();

        if ($generalConfig->headlessMode && Craft::$app->getRequest()->getIsSiteRequest()) {
            $this->_twigOptions['autoescape'] = 'js';
        }

        if (app()->hasDebugModeEnabled()) {
            $this->_twigOptions['debug'] = true;
            $this->_twigOptions['strict_variables'] = true;
        }

        return $this->_twigOptions;
    }

    private function resourceHash(string $key): string
    {
        return sprintf('%x', crc32($key));
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
                $jsName = Json::encode($name);
                // WARNING: the curly braces are needed here no matter what PhpStorm thinks
                // https://youtrack.jetbrains.com/issue/WI-60044
                $js .= "  Craft.{$property}.push($jsName);\n";
            }
        }
        $js .= '}';
        $this->registerJs($js, self::POS_HEAD);
    }

    /**
     * Renders an element’s chip HTML.
     *
     * @param array $context
     * @return string|null
     */
    public function elementChipHtml(array $context): ?string
    {
        Deprecator::log('hook:cp.elements.element', 'The `_elements/element.twig` template and `cp.elements.element` template hook are deprecated. The `elementChip()` function should be used instead.');

        if (!isset($context['element'])) {
            return null;
        }

        if (isset($context['size']) && in_array($context['size'], [Cp::CHIP_SIZE_SMALL, Cp::CHIP_SIZE_LARGE], true)) {
            $size = $context['size'];
        } else {
            $size = (isset($context['viewMode']) && $context['viewMode'] === 'thumbs') ? Cp::CHIP_SIZE_LARGE : Cp::CHIP_SIZE_SMALL;
        }

        return Cp::elementHtml(
            $context['element'],
            context: $context['context'] ?? 'index',
            size: $size,
            inputName: $context['name'] ?? null,
            single: $context['single'] ?? false,
            autoReload: $context['autoReload'] ?? true,
        );
    }

    public static function registerEvents(): void
    {
        Event::listen(RegisterCpTemplateRoots::class, function(RegisterCpTemplateRoots $event) {
            if (!YiiEvent::hasHandlers(self::class, self::EVENT_REGISTER_CP_TEMPLATE_ROOTS)) {
                return;
            }

            $yiiEvent = new RegisterTemplateRootsEvent();
            YiiEvent::trigger(self::class, self::EVENT_REGISTER_CP_TEMPLATE_ROOTS, $yiiEvent);
            $event->roots = $yiiEvent->roots;
        });

        Event::listen(RegisterSiteTemplateRoots::class, function(RegisterSiteTemplateRoots $event) {
            if (!YiiEvent::hasHandlers(self::class, self::EVENT_REGISTER_SITE_TEMPLATE_ROOTS)) {
                return;
            }

            $yiiEvent = new RegisterTemplateRootsEvent();
            YiiEvent::trigger(self::class, self::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, $yiiEvent);
            $event->roots = $yiiEvent->roots;
        });
    }
}
