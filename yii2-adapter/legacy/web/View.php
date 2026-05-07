<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\events\AssetBundleEvent;
use craft\events\CreateTwigEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\TemplateEvent;
use craft\helpers\Cp;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Twig\Environment;
use CraftCms\Cms\Twig\Events\PageEnded;
use CraftCms\Cms\Twig\Events\PageStarting;
use CraftCms\Cms\Twig\Events\PageTemplateRendered;
use CraftCms\Cms\Twig\Events\PageTemplateRendering;
use CraftCms\Cms\Twig\Events\TemplateRendered;
use CraftCms\Cms\Twig\Events\TemplateRendering;
use CraftCms\Cms\Twig\Events\TwigCreated;
use CraftCms\Cms\Twig\PageLifecycle;
use CraftCms\Cms\Twig\TemplateRenderer;
use CraftCms\Cms\Twig\TemplateResolver;
use CraftCms\Cms\Twig\Twig;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\Events\CpTemplateRootsResolving;
use CraftCms\Cms\View\Events\SiteTemplateRootsResolving;
use CraftCms\Cms\View\Events\ViewAssetsRendering;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\TemplateHooks;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\Event;
use Throwable;
use Twig\Error\LoaderError as TwigLoaderError;
use Twig\Error\RuntimeError as TwigRuntimeError;
use Twig\Error\SyntaxError as TwigSyntaxError;
use Twig\Extension\ExtensionInterface;
use yii\base\Exception;
use yii\web\AssetBundle as YiiAssetBundle;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * {@inheritdoc}
 *
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
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 */
class View extends \yii\web\View
{
    /**
     * @event CreateTwigEvent The event that is triggered when a Twig environment is created.
     *
     * @see createTwig()
     * @since 4.3.0
     * @deprecated 6.0.0 use {@see TwigCreated} instead.
     */
    public const EVENT_AFTER_CREATE_TWIG = 'afterCreateTwig';

    /**
     * @event RegisterTemplateRootsEvent The event that is triggered when registering control panel template roots
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\Events\CpTemplateRootsResolving} instead.
     */
    public const EVENT_REGISTER_CP_TEMPLATE_ROOTS = 'registerCpTemplateRoots';

    /**
     * @event RegisterTemplateRootsEvent The event that is triggered when registering site template roots
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\Events\SiteTemplateRootsResolving} instead.
     */
    public const EVENT_REGISTER_SITE_TEMPLATE_ROOTS = 'registerSiteTemplateRoots';

    /**
     * @event TemplateEvent The event that is triggered before a template gets rendered
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Twig\Events\TemplateRendering} instead.
     */
    public const EVENT_BEFORE_RENDER_TEMPLATE = 'beforeRenderTemplate';

    /**
     * @event TemplateEvent The event that is triggered after a template gets rendered
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Twig\Events\TemplateRendered} instead.
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
     *
     * @since 4.5.0
     */
    public const EVENT_AFTER_REGISTER_ASSET_BUNDLE = 'afterRegisterAssetBundle';

    /**
     * @const TEMPLATE_MODE_CP
     *
     * @deprecated 6.0.0 use {@see TemplateMode::Cp} instead.
     */
    public const TEMPLATE_MODE_CP = 'cp';

    /**
     * @const TEMPLATE_MODE_SITE
     *
     * @deprecated 6.0.0 use {@see TemplateMode::Site} instead.
     */
    public const TEMPLATE_MODE_SITE = 'site';

    /**
     * @var bool Whether to minify CSS registered with [[registerCss()]]
     *
     * @since 3.4.0
     * @deprecated in 3.6.0.
     */
    public $minifyCss = false;

    /**
     * @var bool Whether to minify JS registered with [[registerJs()]]
     *
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
     *                            These are kept in the adapter (not the registry) because they require
     *                            jQuery wrapping at render time.
     */
    private array $_readyJs = [];

    /**
     * @var array<string, string> JS registered at POS_LOAD, keyed by key.
     *                            These are kept in the adapter (not the registry) because they require
     *                            jQuery wrapping at render time.
     */
    private array $_loadJs = [];

    /**
     * @var list<array{ready: array<string, string>, load: array<string, string>, begin: array<string, string>}>
     *                                                                                                           Buffer stack for POS_READY/POS_LOAD/POS_BEGIN JS.
     */
    private array $_readyLoadBuffers = [];

    /**
     * When true, the ViewAssetsRendering listener skips flushing _readyJs/_loadJs
     * so that placeholderHtml() can handle jQuery wrapping itself.
     */
    private bool $_skipReadyLoadFlush = false;

    /**
     * @var array<string, int> Maps JS keys to their original Yii2 position (POS_HEAD or POS_BEGIN)
     *                         when both map to Position::Head. Used by clearJsBuffer to reconstruct accurate position keys.
     */
    private array $_jsOriginalPositions = [];

    /**
     * @var list<array<string, int>> Buffer stack for.
     */
    private array $_jsOriginalPositionBuffers = [];

    /**
     * @var string[]
     *
     * @see registerAssetFiles()
     * @see setRegisteredAssetBundles()
     */
    private array $_registeredAssetBundles = [];

    /**
     * @var string[]
     *
     * @see registerJsFile()
     * @see setRegisteredJsfiles()
     */
    private array $_registeredJsFiles = [];

    private ?HtmlStack $_registry = null;

    private function registry(): HtmlStack
    {
        return $this->_registry ??= app(HtmlStack::class);
    }

    private ?TemplateHooks $_templateHooks = null;

    private function templateHooks(): TemplateHooks
    {
        return $this->_templateHooks ??= app(TemplateHooks::class);
    }

    /**
     * {@inheritdoc}
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
     * @deprecated 6.0.0 use {@see Twig::get()} instead.
     */
    public function getTwig(?string $templateMode = null): Environment
    {
        Deprecator::log(__METHOD__, '`craft\web\View::getTwig()` has been deprecated. Use `CraftCms\Cms\Twig\Twig::get()` instead.');

        $mode = TemplateMode::tryFrom($templateMode) ?? TemplateMode::get();

        return app(Twig::class)->get($mode);
    }

    /**
     * Sets the Twig environment for the current template mode.
     *
     *
     * @since 5.6.0
     * @deprecated 6.0.0 use {@see Twig::set()} instead.
     */
    public function setTwig(Environment $twig): void
    {
        Deprecator::log(__METHOD__, '`craft\web\View::setTwig()` has been deprecated. Use `CraftCms\Cms\Twig\Twig::set()` instead.');

        app(Twig::class)->set($twig);
    }

    /**
     * Creates a new Twig environment.
     *
     * @deprecated 6.0.0 use {@see Twig::create()} instead.
     */
    public function createTwig(): Environment
    {
        Deprecator::log(__METHOD__, '`craft\web\View::createTwig()` has been deprecated. Use `CraftCms\Cms\Twig\Twig::create()` instead.');

        return app(Twig::class)->create();
    }

    /**
     * Registers a new Twig extension both CP and site templates.
     *
     *
     * @deprecated 6.0.0 use {@see Twig::registerExtension()} instead.
     */
    public function registerTwigExtension(ExtensionInterface $extension): void
    {
        Deprecator::log(__METHOD__, '`craft\web\View::registerTwigExtension()` has been deprecated. Use `CraftCms\Cms\Twig\Twig::registerExtension()` instead.');

        app(Twig::class)->registerExtension($extension);
    }

    /**
     * Registers a new Twig extension for CP templates.
     *
     *
     * @since 5.5.0
     * @deprecated 6.0.0 use {@see Twig::registerExtension($extension, TemplateMode::Cp)} instead.
     */
    public function registerCpTwigExtension(ExtensionInterface $extension): void
    {
        Deprecator::log(__METHOD__, '`craft\web\View::registerCpTwigExtension()` has been deprecated. Use `CraftCms\Cms\Twig\Twig::registerExtension($extension, TemplateMode::Cp)` instead.');

        app(Twig::class)->registerExtension($extension, TemplateMode::Cp);
    }

    /**
     * Registers a new Twig extension for site templates.
     *
     *
     * @since 5.5.0
     * @deprecated 6.0.0 use {@see Twig::registerExtension($extension, TemplateMode::Site)} instead.
     */
    public function registerSiteTwigExtension(ExtensionInterface $extension): void
    {
        Deprecator::log(__METHOD__, '`craft\web\View::registerSiteTwigExtension()` has been deprecated. Use `CraftCms\Cms\Twig\Twig::registerExtension($extension, TemplateMode::Site)` instead.');

        app(Twig::class)->registerExtension($extension, TemplateMode::Site);
    }

    /**
     * Returns whether a template is currently being rendered.
     *
     * @return bool Whether a template is currently being rendered.
     * @deprecated 6.0.0 use {@see TemplateRenderer::isRenderingTemplate()} instead.
     */
    public function getIsRenderingTemplate(): bool
    {
        Deprecator::log(__METHOD__, '`craft\web\View::getIsRenderingTemplate()` has been deprecated. Use `CraftCms\Cms\Twig\TemplateRenderer::isRenderingTemplate` instead.');

        return app(TemplateRenderer::class)->isRenderingTemplate();
    }

    /**
     * Renders a Twig template.
     *
     * @param  string  $template  The name of the template to load
     * @param  array  $variables  The variables that should be available to the template
     * @param  string|null  $templateMode  The template mode to use
     * @return string the rendering result
     *
     * @throws TwigLoaderError
     * @throws TwigRuntimeError
     * @throws TwigSyntaxError
     * @throws Exception if $templateMode is invalid
     * @deprecated 6.0.0 use {@see TemplateRenderer::renderTemplate()} instead.
     */
    public function renderTemplate(string $template, array $variables = [], ?string $templateMode = null): string
    {
        Deprecator::log(__METHOD__, '`craft\web\View::renderTemplate()` has been deprecated. Use `CraftCms\Cms\Twig\TemplateRenderer::renderTemplate()` or the `template()` helper instead.');

        $templateMode = $templateMode
            ? TemplateMode::from($templateMode)
            : TemplateMode::get();

        return app(TemplateRenderer::class)->renderTemplate($template, $variables, $templateMode);
    }

    /**
     * Renders a Twig template in a sandboxed environment.
     *
     * @param  string  $template  The name of the template to load
     * @param  array  $variables  The variables that should be available to the template
     * @param  string|null  $templateMode  The template mode to use
     * @return string the rendering result
     *
     * @throws TwigLoaderError
     * @throws TwigRuntimeError
     * @throws TwigSyntaxError
     * @throws Exception if $templateMode is invalid
     *
     * @see renderTemplate()
     * @since 4.17.0
     * @deprecated 6.0.0 use {@see TemplateRenderer::renderSandboxedTemplate()} instead.
     */
    public function renderSandboxedTemplate(string $template, array $variables = [], ?string $templateMode = null): string
    {
        Deprecator::log(__METHOD__, '`craft\web\View::renderSandboxedTemplate()` has been deprecated. Use `CraftCms\Cms\Twig\TemplateRenderer::renderSandboxedTemplate()` or the `sandboxedTemplate()` helper instead.');

        return app(TemplateRenderer::class)->renderSandboxedTemplate($template, $variables, $templateMode ? TemplateMode::from($templateMode) : null);
    }

    /**
     * Returns whether a page template is currently being rendered.
     *
     * @return bool Whether a page template is currently being rendered.
     * @deprecated 6.0.0 use {@see TemplateRenderer::isRenderingPageTemplate()} instead.
     */
    public function getIsRenderingPageTemplate(): bool
    {
        Deprecator::log(__METHOD__, '`craft\web\View::getIsRenderingPageTemplate()` has been deprecated. Use `CraftCms\Cms\Twig\TemplateRenderer::isRenderingPageTemplate` instead.');

        return app(TemplateRenderer::class)->isRenderingPageTemplate();
    }

    /**
     * Renders a Twig template that represents an entire web page.
     *
     * @param  string  $template  The name of the template to load
     * @param  array  $variables  The variables that should be available to the template
     * @param  string|null  $templateMode  The template mode to use
     * @return string the rendering result
     *
     * @throws TwigLoaderError
     * @throws TwigRuntimeError
     * @throws TwigSyntaxError
     * @throws Exception if $templateMode is invalid
     * @deprecated 6.0.0 use {@see TemplateRenderer::renderPageTemplate()} instead.
     */
    public function renderPageTemplate(string $template, array $variables = [], ?string $templateMode = null): string
    {
        Deprecator::log(__METHOD__, '`craft\web\View::renderPageTemplate()` has been deprecated. Use `CraftCms\Cms\Twig\TemplateRenderer::renderPageTemplate()` or the `pageTemplate()` helper instead.');

        return app(TemplateRenderer::class)->renderPageTemplate($template, $variables, $templateMode ? TemplateMode::from($templateMode) : null);
    }

    /**
     * Renders a template defined by a string.
     *
     * @param  string  $template  The source template string.
     * @param  array  $variables  Any variables that should be available to the template.
     * @param  string  $templateMode  The template mode to use.
     * @param  bool  $escapeHtml  Whether dynamic HTML should be escaped
     * @return string The rendered template.
     *
     * @throws TwigLoaderError
     * @throws TwigSyntaxError
     * @deprecated 6.0.0 use {@see TemplateRenderer::renderString()} instead.
     */
    public function renderString(string $template, array $variables = [], string $templateMode = TemplateMode::Site->value, bool $escapeHtml = false): string
    {
        Deprecator::log(__METHOD__, '`craft\web\View::renderString()` has been deprecated. Use `CraftCms\Cms\Twig\TemplateRenderer::renderString()` or the `renderString()` helper instead.');

        return app(TemplateRenderer::class)->renderString($template, $variables, TemplateMode::from($templateMode), $escapeHtml);
    }

    /**
     * Renders a template defined by a string in a sandboxed environment.
     *
     * @param  string  $template  The source template string.
     * @param  array  $variables  Any variables that should be available to the template.
     * @param  string  $templateMode  The template mode to use.
     * @param  bool  $escapeHtml  Whether dynamic HTML should be escaped
     * @return string The rendered template.
     *
     * @throws TwigLoaderError
     * @throws TwigSyntaxError
     *
     * @see renderString()
     * @since 4.17.0
     * @deprecated 6.0.0 use {@see TemplateRenderer::renderSandboxedString()} instead.
     */
    public function renderSandboxedString(string $template, array $variables = [], string $templateMode = TemplateMode::Site->value, bool $escapeHtml = false): string
    {
        Deprecator::log(__METHOD__, '`craft\web\View::renderSandboxedString()` has been deprecated. Use `CraftCms\Cms\Twig\TemplateRenderer::renderSandboxedString()` or the `renderSandboxedString()` helper instead.');

        return app(TemplateRenderer::class)->renderSandboxedString($template, $variables, TemplateMode::from($templateMode), $escapeHtml);
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
     * @param  string  $template  the source template string
     * @param  mixed  $object  the object that should be passed into the template
     * @param  array  $variables  any additional variables that should be available to the template
     * @param  string  $templateMode  The template mode to use.
     * @return string The rendered template.
     *
     * @throws Exception in case of failure
     * @throws Throwable in case of failure
     * @deprecated 6.0.0 use {@see TemplateRenderer::renderObjectTemplate()} instead.
     */
    public function renderObjectTemplate(string $template, mixed $object, array $variables = [], string $templateMode = TemplateMode::Site->value): string
    {
        Deprecator::log(__METHOD__, '`craft\web\View::renderObjectTemplate()` has been deprecated. Use `CraftCms\Cms\Twig\TemplateRenderer::renderObjectTemplate()` or the `renderObjectTemplate()` helper instead.');

        return app(TemplateRenderer::class)->renderObjectTemplate($template, $object, $variables, TemplateMode::from($templateMode));
    }

    /**
     * Renders an object template in a sandboxed environment.
     *
     * @param  string  $template  the source template string
     * @param  mixed  $object  the object that should be passed into the template
     * @param  array  $variables  any additional variables that should be available to the template
     * @param  string  $templateMode  The template mode to use.
     * @return string The rendered template.
     *
     * @throws Exception in case of failure
     * @throws Throwable in case of failure
     *
     * @see renderObjectTemplate()
     * @since 4.17.0
     * @deprecated 6.0.0 use {@see TemplateRenderer::renderSandboxedObjectTemplate()} instead.
     */
    public function renderSandboxedObjectTemplate(
        string $template,
        mixed $object,
        array $variables = [],
        string $templateMode = TemplateMode::Site->value,
    ): string {
        Deprecator::log(__METHOD__, '`craft\web\View::renderSandboxedObjectTemplate()` has been deprecated. Use `CraftCms\Cms\Twig\TemplateRenderer::renderSandboxedObjectTemplate()` or the `renderSandboxedObjectTemplate()` helper instead.');

        return app(TemplateRenderer::class)->renderSandboxedObjectTemplate($template, $object, $variables, TemplateMode::from($templateMode));
    }

    /**
     * Normalizes an object template for [[renderObjectTemplate()]].
     *
     * @param string $template
     * @return string
     * @deprecated 6.0.0 use {@see TemplateRenderer::normalizeObjectTemplate()} instead.
     */
    public function normalizeObjectTemplate(string $template): string
    {
        Deprecator::log(__METHOD__, '`craft\web\View::normalizeObjectTemplate()` has been deprecated. Use `CraftCms\Cms\Twig\TemplateRenderer::normalizeObjectTemplate()` instead.');



        return app(TemplateRenderer::class)->normalizeObjectTemplate($template);
    }

    /**
     * Returns whether a template exists.
     *
     * Internally, this will just call [[resolveTemplate()]] with the given template name, and return whether that
     * method found anything.
     *
     * @param  string  $name  The name of the template.
     * @param  string|null  $templateMode  The template mode to use.
     * @param  bool  $publicOnly  Whether to only look for public templates (template paths that don’t start with the private template trigger).
     * @return bool Whether the template exists.
     *
     * @deprecated 6.0.0 use {@see TemplateResolver::exists()} instead.
     */
    public function doesTemplateExist(string $name, ?string $templateMode = null, bool $publicOnly = false): bool
    {
        return app(TemplateResolver::class)->exists($name, $templateMode ? TemplateMode::from($templateMode) : null, $publicOnly);
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
     * @param  string|null  $templateMode  The template mode to use.
     * @param  bool  $publicOnly  Whether to only look for public templates (template paths that don’t start with the private template trigger).
     * @return string|false The path to the template if it exists, or `false`.
     *
     * @throws TwigLoaderError
     *
     * @deprecated 6.0.0 use {@see TemplateResolver::resolve()} instead.
     */
    public function resolveTemplate(string $name, ?string $templateMode = null, bool $publicOnly = false): string|false
    {
        return app(TemplateResolver::class)->resolve($name, $templateMode ? TemplateMode::from($templateMode) : null, $publicOnly);
    }

    /**
     * Returns any registered control panel template roots.
     *
     * @deprecated 6.0.0 use {@see TemplateMode::templateRoots()} instead.
     */
    public function getCpTemplateRoots(): array
    {
        return TemplateMode::Cp->templateRoots();
    }

    /**
     * Returns any registered site template roots.
     *
     * @deprecated 6.0.0 use {@see TemplateMode::templateRoots()} instead.
     */
    public function getSiteTemplateRoots(): array
    {
        return TemplateMode::Site->templateRoots();
    }

    /**
     * @inheritdoc
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::js()} instead.
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
                $this->registry()->js($js, Position::BodyBegin, $key);
                $this->_jsOriginalPositions[$key] = $position;
            })(),
            self::POS_END => (function() use ($js, $key, $position) {
                $this->registry()->js($js, Position::BodyEnd, $key);
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
     * @param  callable  $jsFn  callback function that returns the JS code to be registered.
     * @param  array  $vars  Array of variables that will be JSON-encoded before being passed to `$jsFn`.
     * @param  int  $position  the position at which the JS script tag should be inserted
     *                         in a page. The possible values are:
     *
     * - [[POS_HEAD]]: in the head section
     * - [[POS_BEGIN]]: at the beginning of the body section
     * - [[POS_END]]: at the end of the body section
     * - [[POS_LOAD]]: executed once the window load event fires.
     * - [[POS_READY]]: executed once the DOMContentLoaded event fires. This is the default value.
     * @param  string|null  $key  the key that identifies the JS code block. If null, it will use
     *                            $js as the key. If two JS code blocks are registered with the same key, the latter
     *                            will overwrite the former.
     *
     * @since 3.7.31
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::jsWithVars()} instead.
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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::startJsBuffer()} instead.
     */
    public function startJsBuffer(): void
    {
        $this->registry()->startJsBuffer();
        $this->_jsBufferDepth++;

        $this->_readyLoadBuffers[] = [
            'ready' => $this->_readyJs,
            'load' => $this->_loadJs,
        ];
        $this->_readyJs = [];
        $this->_loadJs = [];

        $this->_jsOriginalPositionBuffers[] = $this->_jsOriginalPositions;
        $this->_jsOriginalPositions = [];
    }

    /**
     * Clears and ends a buffer started via [[startJsBuffer()]], returning any JavaScript code that was registered while
     * the buffer was active.
     *
     * @param  bool  $scriptTag  Whether the returned JavaScript code should be wrapped in a `<script>` tag.
     * @param  bool  $combine  Whether the JavaScript code should be returned in a combined blob. (Position and key info will be lost.)
     * @return string|array|false The JavaScript code that was registered while the buffer was active, or `false` if there wasn't an active buffer.
     *
     * @see startJsBuffer()
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::clearJsBuffer()} instead.
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

        if (!empty($this->_readyLoadBuffers)) {
            $previousReadyLoad = array_pop($this->_readyLoadBuffers);
            $this->_readyJs = $previousReadyLoad['ready'];
            $this->_loadJs = $previousReadyLoad['load'];
        } else {
            $this->_readyJs = [];
            $this->_loadJs = [];
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
        // Position::BodyBegin entries map back to POS_BEGIN
        if (!empty($registryState[Position::BodyBegin->value])) {
            foreach ($registryState[Position::BodyBegin->value] as $key => $js) {
                $originalPos = $bufferedPositions[$key] ?? self::POS_BEGIN;
                $bufferedJs[$originalPos][$key] = $js;
            }
        }
        // Position::BodyEnd entries map back to POS_END
        if (!empty($registryState[Position::BodyEnd->value])) {
            foreach ($registryState[Position::BodyEnd->value] as $key => $js) {
                $originalPos = $bufferedPositions[$key] ?? self::POS_END;
                $bufferedJs[$originalPos][$key] = $js;
            }
        }
        if (!empty($registryState[Position::Ready->value])) {
            foreach ($registryState[Position::Ready->value] as $key => $js) {
                $originalPos = $bufferedPositions[$key] ?? self::POS_READY;
                $bufferedJs[$originalPos][$key] = $js;
            }
        }
        if (!empty($registryState[Position::Load->value])) {
            foreach ($registryState[Position::Load->value] as $key => $js) {
                $originalPos = $bufferedPositions[$key] ?? self::POS_LOAD;
                $bufferedJs[$originalPos][$key] = $js;
            }
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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::startScriptBuffer()} instead.
     */
    public function startScriptBuffer(): void
    {
        $this->registry()->startScriptBuffer();
        $this->_scriptBufferDepth++;
    }

    /**
     * Clears and ends a buffer started via [[startScriptBuffer()]], returning any `<script>` tags that were registered
     * while the buffer was active.
     *
     * @return array|false The `<script>` tags that were registered while the buffer was active, or `false` if there wasn't an active buffer.
     *
     * @see startScriptBuffer()
     * @since 3.7.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::clearScriptBuffer()} instead.
     */
    public function clearScriptBuffer(): array|false
    {
        if ($this->_scriptBufferDepth === 0) {
            return false;
        }

        // Capture what was registered during the buffer and restore pre-buffer state
        $registryState = $this->registry()->clearScriptBuffer();
        $this->_scriptBufferDepth--;

        // Map registry positions back to Yii2 positions
        $bufferedScripts = [];
        if (!empty($registryState[Position::Head->value])) {
            $bufferedScripts[self::POS_HEAD] = array_map(fn($v) => (string) $v, $registryState[Position::Head->value]);
        }
        if (!empty($registryState[Position::BodyBegin->value])) {
            $bufferedScripts[self::POS_BEGIN] = array_map(fn($v) => (string) $v, $registryState[Position::BodyBegin->value]);
        }
        if (!empty($registryState[Position::BodyEnd->value])) {
            $bufferedScripts[self::POS_END] = array_map(fn($v) => (string) $v, $registryState[Position::BodyEnd->value]);
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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::startCssBuffer()} instead.
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
     *
     * @see startCssBuffer()
     * @since 3.7.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::clearCssBuffer()} instead.
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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::startCssFileBuffer()} instead.
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
     *
     * @see startCssFileBuffer()
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::clearCssFileBuffer()} instead.
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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::startJsFileBuffer()} instead.
     */
    public function startJsFileBuffer(): void
    {
        $this->registry()->startJsFileBuffer();
        $this->_jsFileBufferDepth++;
    }

    /**
     * Clears and ends a buffer started via [[startJsFileBuffer()]], returning any `<script>` tags that were registered
     * while the buffer was active.
     *
     * @return array|false The `<script>` tags that were registered while the buffer was active (indexed by position), or `false` if there wasn't an active buffer.
     *
     * @see startJsFileBuffer()
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::clearJsFileBuffer()} instead.
     */
    public function clearJsFileBuffer(): array|false
    {
        if ($this->_jsFileBufferDepth === 0) {
            return false;
        }

        $registryState = $this->registry()->clearJsFileBuffer();
        $this->_jsFileBufferDepth--;

        // Map registry positions back to Yii2 positions
        $bufferedJsFiles = [];
        if (!empty($registryState[Position::Head->value])) {
            $bufferedJsFiles[self::POS_HEAD] = $registryState[Position::Head->value];
        }
        if (!empty($registryState[Position::BodyBegin->value])) {
            $bufferedJsFiles[self::POS_BEGIN] = $registryState[Position::BodyBegin->value];
        }
        if (!empty($registryState[Position::BodyEnd->value])) {
            $bufferedJsFiles[self::POS_END] = $registryState[Position::BodyEnd->value];
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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::startHtmlBuffer()} instead.
     */
    public function startHtmlBuffer(): void
    {
        $this->registry()->startHtmlBuffer();
        $this->_htmlBufferDepth++;
    }

    /**
     * Clears and ends a buffer started via [[startHtmlBuffer()]], returning any html tags that were registered
     * while the buffer was active.
     *
     * @return array|false The html that was registered while the buffer was active or `false` if there wasn't an active buffer.
     *
     * @since 4.3.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::clearHtmlBuffer()} instead.
     */
    public function clearHtmlBuffer(): array|false
    {
        if ($this->_htmlBufferDepth === 0) {
            return false;
        }

        $registryState = $this->registry()->clearHtmlBuffer();
        $this->_htmlBufferDepth--;

        // Map registry positions back to Yii2 positions
        $bufferedHtml = [];
        if (!empty($registryState[Position::Head->value])) {
            $bufferedHtml[self::POS_HEAD] = $registryState[Position::Head->value];
        }
        if (!empty($registryState[Position::BodyBegin->value])) {
            $bufferedHtml[self::POS_BEGIN] = $registryState[Position::BodyBegin->value];
        }
        if (!empty($registryState[Position::BodyEnd->value])) {
            $bufferedHtml[self::POS_END] = $registryState[Position::BodyEnd->value];
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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::startMetaTagBuffer()} instead.
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
     *
     * @see startMetaTagBuffer()
     * @since 4.5.8
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::clearMetaTagBuffer()} instead.
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
     *
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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::startJsImportBuffer()} instead.
     */
    public function startJsImportBuffer(): void
    {
        $this->registry()->startJsImportBuffer();
        $this->_jsImportBufferDepth++;
    }

    /**
     * @inheritdoc
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::clearJsImportBuffer()} instead.
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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::jsFile()} instead.
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

        $registryPosition = match ($position) {
            self::POS_HEAD => Position::Head->value,
            self::POS_BEGIN => Position::BodyBegin->value,
            default => Position::BodyEnd->value,
        };
        $options['position'] = $registryPosition;

        $this->registry()->jsFile($url, $options, $key);
    }

    /**
     * @inheritdoc
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::metaTag()} instead.
     */
    public function registerMetaTag($options, $key = null): void
    {
        $this->registry()->metaTag($options, $key);
    }

    /**
     * @inheritdoc
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::linkTag()} instead.
     */
    public function registerLinkTag($options, $key = null): void
    {
        $this->registry()->linkTag($options, $key);
    }

    /**
     * @inheritdoc
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::css()} instead.
     */
    public function registerCss($css, $options = [], $key = null): void
    {
        $this->registry()->css($css, $options, $key);
    }

    /**
     * @inheritdoc
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::cssFile()} instead.
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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::script()} instead.
     */
    public function registerScript(string $script, int $position = self::POS_END, array $options = [], ?string $key = null): void
    {
        $key ??= md5($script);

        $registryPosition = match ($position) {
            self::POS_HEAD => Position::Head,
            self::POS_BEGIN => Position::BodyBegin,
            default => Position::BodyEnd,
        };

        $this->registry()->script($script, $registryPosition, $options, $key);
    }

    /**
     * Registers a generic `<script>` tag with the given variables, pre-JSON-encoded.
     *
     * @param  callable  $scriptFn  callback function that returns the JS code to be registered.
     * @param  array  $vars  Array of variables that will be JSON-encoded before being passed to `$scriptFn`
     * @param  int  $position  the position at which the JS script tag should be inserted
     *                         in a page. The possible values are:
     *                         - [[POS_HEAD]]: in the head section
     *                         - [[POS_BEGIN]]: at the beginning of the body section
     *                         - [[POS_END]]: at the end of the body section
     * @param  array  $options  the HTML attributes for the `<script>` tag.
     * @param  string|null  $key  the key that identifies the generic `<script>` code block. If null, it will use
     *                            $script as the key. If two generic `<script>` code blocks are registered with the same key, the latter
     *                            will overwrite the former.
     *
     * @since 5.6.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::scriptWithVars()} instead.
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
     * @param  string  $html  the HTML code to be registered
     * @param  int  $position  the position at which the HTML code should be inserted in the page. Possible values are:
     *                         - [[POS_HEAD]]: in the head section
     *                         - [[POS_BEGIN]]: at the beginning of the body section
     *                         - [[POS_END]]: at the end of the body section
     * @param  string|null  $key  the key that identifies the HTML code. If null, it will use a hash of the HTML as the key.
     *                            If two HTML code blocks are registered with the same position and key, the latter will overwrite the former.
     *
     * @since 3.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::html()} instead.
     */
    public function registerHtml(string $html, int $position = self::POS_END, ?string $key = null): void
    {
        $key ??= md5($html);

        $registryPosition = match ($position) {
            self::POS_HEAD => Position::Head,
            self::POS_BEGIN => Position::BodyBegin,
            default => Position::BodyEnd,
        };

        $this->registry()->html($html, $registryPosition, $key);
    }

    /**
     * Registers a JavaScript import map entry to be injected into the final page response.
     *
     * @param  string  $key  The module specifier.
     * @param  string  $value  The URL or path to the resource the key will resolve to.
     *
     * @since 5.6.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::jsImport()} instead.
     */
    public function registerJsImport(string $key, string $value): void
    {
        $this->registry()->jsImport($key, $value);
    }

    public function head()
    {
        app(PageLifecycle::class)->head();
    }

    public function beginBody()
    {
        app(PageLifecycle::class)->beginBody();
    }

    /**
     * {@inheritdoc}
     */
    public function endBody(): void
    {
        app(PageLifecycle::class)->endBody();
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
     * @param  bool  $clear  Whether the content should be cleared from the queue (default is true)
     * @return string the rendered content
     * @deprecated 6.0.0 use {@see HtmlStack::headHtml()} instead.
     */
    public function getHeadHtml(bool $clear = true): string
    {
        Deprecator::log(__METHOD__, '`craft\web\View::getHeadHtml()` has been deprecated. Use `CraftCms\Cms\View\HtmlStack::headHtml()` instead.');

        return $this->registry()->headHtml(clear: $clear);
    }

    /**
     * Returns the content to be inserted at the end of the body section.
     *
     * This includes:
     * - JS code registered with [[registerJs()]] with the position set to [[POS_BEGIN]], [[POS_END]], [[POS_READY]], or [[POS_LOAD]]
     * - JS files registered with [[registerJsFile()]] with the position set to [[POS_BEGIN]] or [[POS_END]]
     *
     * @param  bool  $clear  Whether the content should be cleared from the queue (default is true)
     * @return string the rendered content
     * @deprecated 6.0.0 use {@see HtmlStack::bodyHtml()} instead.
     */
    public function getBodyHtml(bool $clear = true): string
    {
        Deprecator::log(__METHOD__, '`craft\web\View::getBodyHtml()` has been deprecated. Use `CraftCms\Cms\View\HtmlStack::bodyHtml()` instead.');

        return $this->registry()->bodyHtml(clear: $clear);
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
     * @deprecated 6.0.0 All translations are now loaded in bulk via `window.Craft.translations`.
     */
    public function registerTranslations(string $category, array $messages): void
    {
    }

    /**
     * Registers icons for `Craft.ui.icon()`.
     *
     * @param  string[]  $icons  The icons to be registered
     *
     * @since 5.7.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\HtmlStack::icons()} instead.
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
     *
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
     * @param  string|null  $namespace  The new namespace. Set to null to remove the namespace.
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\InputNamespace::set()} instead.
     */
    public function setNamespace(?string $namespace): void
    {
        InputNamespace::set($namespace);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\DeltaRegistry::registerName()} instead.
     */
    public function registerDeltaName(string $inputName, bool $forceModified = false): void
    {
        DeltaRegistry::registerName($inputName, $forceModified);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\DeltaRegistry::getInitialValues()} instead.
     */
    public function getInitialDeltaValues(): array
    {
        return DeltaRegistry::getInitialValues();
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\DeltaRegistry::setInitialValue()} instead.
     */
    public function setInitialDeltaValue(string $inputName, mixed $value): void
    {
        DeltaRegistry::setInitialValue($inputName, $value);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\DeltaRegistry::isActive()} instead.
     */
    public function getIsDeltaRegistrationActive(): bool
    {
        return DeltaRegistry::isActive();
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\DeltaRegistry::setActive()} instead.
     */
    public function setIsDeltaRegistrationActive(bool $active): void
    {
        DeltaRegistry::setActive($active);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\DeltaRegistry::getNames()} instead.
     */
    public function getDeltaNames(): array
    {
        return DeltaRegistry::getNames();
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\DeltaRegistry::getModifiedNames()} instead.
     */
    public function getModifiedDeltaNames(): array
    {
        return DeltaRegistry::getModifiedNames();
    }

    /**
     * Returns the current template mode (either `site` or `cp`).
     *
     * @return string Either `site` or `cp`.
     *
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
     * @param  string |TemplateMode  $templateMode  Either 'site' or 'cp'
     *
     * @throws Exception if $templateMode is invalid
     *
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
     * @deprecated 6.0.0 use {@see TemplateMode::templatesPath()} instead.
     */
    public function getTemplatesPath(): string
    {
        return TemplateMode::get()->templatesPath();
    }

    /**
     * Sets the base path that templates should be found in.
     *
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
     * @param  callable|string  $html  The HTML code, or a callable that returns the HTML code
     * @param  string|null  $namespace  The namespace. Defaults to the [[getNamespace()|active namespace]].
     * @param  bool  $otherAttributes  Whether `id`, `for`, and other attributes should be namespaced (in addition to `name`)
     * @param  bool  $withClasses  Whether class names should be namespaced as well (affects both `class` attributes and
     *                             class name CSS selectors within `<style>` tags). This will only have an effect if `$otherAttributes` is `true`.
     * @return string The HTML with namespaced attributes
     *
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
     * @param  string  $inputName  The input name that should be namespaced.
     * @param  string|null  $namespace  The namespace. Defaults to the [[getNamespace()|active namespace]].
     * @return string The namespaced input name.
     *
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
     * @param  string  $inputId  The input ID that should be namespaced.
     * @param  string|null  $namespace  The namespace. Defaults to the [[getNamespace()|active namespace]].
     * @return string The namespaced input ID.
     *
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
     * @param  string  $inputName  The input name.
     * @return string The input ID.
     *
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
     * @param  string  $hook  The hook name.
     * @param  callable  $method  The callback function.
     * @param  bool  $append  whether to append the method handler to the end of the existing method list for the hook. If `false`, the method will be
     *                        inserted at the beginning of the existing method list.
     *
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
     * @param  string  $hook  The hook name.
     * @param  array  $context  The current template context.
     * @return string Whatever the hooks returned.
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\TemplateHooks::invoke()} instead.
     */
    public function invokeHook(string $hook, array &$context): string
    {
        return $this->templateHooks()->invoke($hook, $context);
    }

    /**
     * Sets the JS files that should be marked as already registered.
     *
     * @param  string[]  $keys
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
     * @param  string[]  $names  Asset bundle names
     *
     * @since 3.0.10
     * @deprecated 6.0.0
     */
    public function setRegisteredAssetBundles(array $names): void
    {
        $this->_registeredAssetBundles = array_flip($names);
    }

    /**
     * {@inheritdoc}
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
     * Returns HTML that should replace page placeholders after page template rendering.
     *
     * This method renders all registered assets (head, body-begin, body-end) into
     * HTML strings and returns them as an array. It is called by the `PageEnded` event
     * listener in {@see self::registerEvents()} to populate the event properties
     * that `TemplateRenderer::renderPageTemplate()` uses for placeholder replacement.
     *
     * **Asset clearing behavior:** When `$clear` is `true` (the default), this method
     * calls `HtmlStack::headHtml(clear: true)`, `bodyBeginHtml(clear: true)`, and
     * `bodyEndHtml(clear: true)` after rendering, which empties the registry's asset
     * collections for those positions. This prevents assets from being rendered twice
     * (once here and once via the `HtmlStack` fallback in `TemplateRenderer`).
     * Legacy `_readyJs` and `_loadJs` arrays are also cleared.
     *
     * @param  bool  $ajaxMode  Whether to render in AJAX mode (omits certain assets).
     * @param  bool  $clear  Whether to clear rendered assets from the registry after output.
     *                       When `true`, the `HtmlStack` fallback in `TemplateRenderer`
     *                       will produce empty strings since the assets have already been consumed.
     * @return array{headHtml: string, bodyBeginHtml: string, bodyEndHtml: string}
     */
    public function placeholderHtml(bool $ajaxMode = false, bool $clear = true): array
    {
        if (!$ajaxMode && TemplateMode::is(TemplateMode::Cp)) {
            $this->_setJsProperty('registeredJsFiles', $this->_registeredJsFiles);
            $this->_setJsProperty('registeredAssetBundles', $this->_registeredAssetBundles);
        }

        // Prevent the ViewAssetsRendering listener from flushing _readyJs/_loadJs,
        // since renderBodyEndHtml() handles jQuery wrapping for page renders.
        $this->_skipReadyLoadFlush = true;

        // Ensure all queued bundle resources are registered before rendering.
        $this->registerAllAssetFiles();

        $headHtml = $this->renderHeadHtml();
        $bodyBeginHtml = $this->renderBodyBeginHtml();
        $bodyEndHtml = $this->renderBodyEndHtml($ajaxMode);

        $this->_skipReadyLoadFlush = false;

        if ($clear) {
            // Clear the HtmlStack so that TemplateRenderer's ?? fallback to
            // HtmlStack::headHtml() / bodyBeginHtml() / bodyEndHtml() returns
            // empty strings — the assets have already been consumed above.
            $this->registry()->headHtml(clear: true);
            $this->registry()->bodyBeginHtml(clear: true);
            $this->registry()->bodyEndHtml(clear: true);

            $this->_readyJs = [];
            $this->_loadJs = [];
        }

        return [
            'headHtml' => $headHtml,
            'bodyBeginHtml' => $bodyBeginHtml,
            'bodyEndHtml' => $bodyEndHtml,
        ];
    }

    /**
     * {@inheritdoc}
     *
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
     * Performs actions before a page template is rendered.
     *
     * @param  string  $template  The name of the template to render
     * @param  array  $variables  The variables that should be available to the template
     * @param  string  $templateMode  The template mode to use when rendering the template
     *
     * @return bool Whether the template should be rendered
     * @deprecated 6.0.0
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
     * @param  string  $template  The name of the template that was rendered
     * @param  array  $variables  The variables that were available to the template
     * @param  string  $templateMode  The template mode that was used when rendering the template
     * @param  string  $output  The template’s rendering result
     * @deprecated 6.0.0
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
     * {@inheritdoc}
     */
    protected function renderHeadHtml(): string
    {
        return $this->registry()->headHtml(clear: false);
    }

    /**
     * {@inheritdoc}
     */
    protected function renderBodyBeginHtml(): string
    {
        return $this->registry()->bodyBeginHtml(clear: false);
    }

    /**
     * {@inheritdoc}
     */
    protected function renderBodyEndHtml($ajaxMode): string
    {
        $html = $this->registry()->bodyEndHtml(clear: false);

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
                $js = $this->readyJs($this->_readyJs);
                $html .= ($html !== '' ? "\n" : '') . Html::script($js)->render();
            }
            if (!empty($this->_loadJs)) {
                $js = $this->loadJs($this->_loadJs);
                $html .= ($html !== '' ? "\n" : '') . Html::script($js)->render();
            }
        }

        return $html;
    }

    private function readyJs(array $scripts): string
    {
        $js = implode("\n", $scripts);

        return <<<JS
(() => {
  const run = function () {
$js
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => run.call(document), {once: true});
  } else {
    run.call(document);
  }
})();
JS;
    }

    private function loadJs(array $scripts): string
    {
        $js = implode("\n", $scripts);

        return <<<JS
(() => {
  const run = function (event) {
$js
  };

  if (document.readyState === 'complete') {
    run.call(window);
  } else {
    window.addEventListener('load', (event) => run.call(window, event), {once: true});
  }
})();
JS;
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
     */
    protected function registerAllAssetFiles(): void
    {
        foreach ($this->assetBundles as $bundleName => $bundle) {
            $this->registerAssetFiles($bundleName);
        }
    }

    /**
     * Flushes all pending asset registrations into the HtmlStack.
     *
     * Called by the {@see \CraftCms\Cms\View\Events\ViewAssetsRendering} listener to ensure
     * Yii2 asset bundles and POS_READY/POS_LOAD JS are pushed into the registry
     * before it renders.
     */
    public function flushPendingAssets(): void
    {
        $this->registerAssetFlashes();
        $this->registerAllAssetFiles();

        if (!$this->_skipReadyLoadFlush) {
            $this->flushReadyLoadJs();
        }
    }

    /**
     * Pushes any queued POS_READY/POS_LOAD JS into the HtmlStack as BodyEnd JS,
     * then clears the queues.
     */
    private function flushReadyLoadJs(): void
    {
        if (!empty($this->_readyJs)) {
            foreach ($this->_readyJs as $key => $js) {
                $this->registry()->js($js, Position::Ready, is_string($key) ? $key : null);
            }
            $this->_readyJs = [];
        }
        if (!empty($this->_loadJs)) {
            foreach ($this->_loadJs as $key => $js) {
                $this->registry()->js($js, Position::Load, is_string($key) ? $key : null);
            }
            $this->_loadJs = [];
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
     * {@inheritdoc}
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

    private function resourceHash(string $key): string
    {
        return sprintf('%x', crc32($key));
    }

    /**
     * @param  string[]  $names
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
     */
    public function elementChipHtml(array $context): ?string
    {
        Deprecator::log('hook:cp.elements.element', 'The `_elements/element.twig` template and `cp.elements.element` template hook are deprecated. The `elementChip()` function should be used instead.');

        if (!isset($context['element'])) {
            return null;
        }

        if (isset($context['size']) && in_array($context['size'], [ElementHtml::CHIP_SIZE_SMALL, ElementHtml::CHIP_SIZE_LARGE], true)) {
            $size = $context['size'];
        } else {
            $size = (isset($context['viewMode']) && $context['viewMode'] === 'thumbs') ? ElementHtml::CHIP_SIZE_LARGE : ElementHtml::CHIP_SIZE_SMALL;
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
        Event::listen(CpTemplateRootsResolving::class, function(CpTemplateRootsResolving $event) {
            if (!Craft::$app->getView()->hasEventHandlers(self::EVENT_REGISTER_CP_TEMPLATE_ROOTS)) {
                return;
            }

            $yiiEvent = new RegisterTemplateRootsEvent();
            Craft::$app->getView()->trigger(self::EVENT_REGISTER_CP_TEMPLATE_ROOTS, $yiiEvent);
            $event->roots = array_merge($event->roots, $yiiEvent->roots);
        });

        Event::listen(SiteTemplateRootsResolving::class, function(SiteTemplateRootsResolving $event) {
            if (!Craft::$app->getView()->hasEventHandlers(self::EVENT_REGISTER_SITE_TEMPLATE_ROOTS)) {
                return;
            }

            $yiiEvent = new RegisterTemplateRootsEvent();
            Craft::$app->getView()->trigger(self::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, $yiiEvent);
            $event->roots = array_merge($event->roots, $yiiEvent->roots);
        });

        Event::listen(function(TwigCreated $event) {
            if (!Craft::$app->getView()->hasEventHandlers(self::EVENT_AFTER_CREATE_TWIG)) {
                return;
            }

            Craft::$app->getView()->trigger(self::EVENT_AFTER_CREATE_TWIG, new CreateTwigEvent([
                'templateMode' => $event->templateMode->value,
                'twig' => $event->twig,
            ]));
        });

        Event::listen(function(TemplateRendering $event) {
            if (!Craft::$app->getView()->hasEventHandlers(self::EVENT_BEFORE_RENDER_TEMPLATE)) {
                return;
            }

            $yiiEvent = new TemplateEvent([
                'template' => $event->template,
                'variables' => $event->variables,
                'templateMode' => $event->templateMode->value,
            ]);

            Craft::$app->getView()->trigger(self::EVENT_BEFORE_RENDER_TEMPLATE, $yiiEvent);

            $event->isValid = $yiiEvent->isValid;
            $event->template = $yiiEvent->template;
            $event->variables = $yiiEvent->variables;
            $event->templateMode = TemplateMode::from($yiiEvent->templateMode);
        });

        Event::listen(function(PageTemplateRendering $event) {
            if (!Craft::$app->getView()->hasEventHandlers(self::EVENT_BEFORE_RENDER_PAGE_TEMPLATE)) {
                return;
            }

            $yiiEvent = new TemplateEvent([
                'template' => $event->template,
                'variables' => $event->variables,
                'templateMode' => $event->templateMode->value,
            ]);

            Craft::$app->getView()->trigger(self::EVENT_BEFORE_RENDER_PAGE_TEMPLATE, $yiiEvent);

            $event->isValid = $yiiEvent->isValid;
            $event->template = $yiiEvent->template;
            $event->variables = $yiiEvent->variables;
            $event->templateMode = TemplateMode::from($yiiEvent->templateMode);
        });

        Event::listen(function(TemplateRendered $event) {
            if (!Craft::$app->getView()->hasEventHandlers(self::EVENT_AFTER_RENDER_TEMPLATE)) {
                return;
            }

            $yiiEvent = new TemplateEvent([
                'template' => $event->template,
                'variables' => $event->variables,
                'templateMode' => $event->templateMode->value,
                'output' => $event->output,
            ]);

            Craft::$app->getView()->trigger(self::EVENT_AFTER_RENDER_TEMPLATE, $yiiEvent);

            $event->output = $yiiEvent->output;
        });

        Event::listen(function(PageTemplateRendered $event) {
            if (!Craft::$app->getView()->hasEventHandlers(self::EVENT_AFTER_RENDER_PAGE_TEMPLATE)) {
                return;
            }

            $yiiEvent = new TemplateEvent([
                'template' => $event->template,
                'variables' => $event->variables,
                'templateMode' => $event->templateMode->value,
                'output' => $event->output,
            ]);

            Craft::$app->getView()->trigger(self::EVENT_AFTER_RENDER_PAGE_TEMPLATE, $yiiEvent);

            $event->output = $yiiEvent->output;
        });

        Event::listen(function(PageStarting $event) {
            Craft::$app->getView()->trigger(self::EVENT_BEGIN_PAGE);
        });

        Event::listen(function(PageEnded $event) {
            Craft::$app->getView()->trigger(self::EVENT_END_PAGE);

            $html = Craft::$app->getView()->placeholderHtml();
            $event->headHtml = $html['headHtml'];
            $event->bodyBeginHtml = $html['bodyBeginHtml'];
            $event->bodyEndHtml = $html['bodyEndHtml'];
        });

        Event::listen(function(ViewAssetsRendering $event) {
            Craft::$app->getView()->flushPendingAssets();
        });
    }
}
