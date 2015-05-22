<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web;

use Craft;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\events\Event;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\ElementHelper;
use craft\app\helpers\HtmlHelper;
use craft\app\helpers\IOHelper;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\PathHelper;
use craft\app\helpers\StringHelper;
use craft\app\services\Plugins;
use craft\app\web\assets\AppAsset;
use craft\app\web\twig\Environment;
use craft\app\web\twig\Extension;
use craft\app\web\twig\StringTemplate;
use craft\app\web\twig\Template;
use craft\app\web\twig\TemplateLoader;
use yii\base\InvalidParamException;
use yii\helpers\Html;
use yii\web\AssetBundle;

/**
 * @inheritdoc
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class View extends \yii\web\View
{
	// Public Methods
	// =========================================================================

	/**
	 * @var array the registered hi-res CSS code blocks.
	 * @see registerHiResCss()
	 */
	public $hiResCss;

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
		$this->hook('cp.elements.element', [$this, '_getCpElementHtml']);
	}

	/**
	 * Returns the Twig Environment instance for a given template loader class.
	 *
	 * @param string $loaderClass The name of the class that should be initialized as the Twig instance’s template
	 *                            loader. If no class is passed in, [[TemplateLoader]] will be used.
	 *
	 * @return Environment The Twig Environment instance.
	 */
	public function getTwig($loaderClass = null)
	{
		if (!$loaderClass)
		{
			$loaderClass = 'craft\app\web\twig\TemplateLoader';
		}

		if (!isset($this->_twigs[$loaderClass]))
		{
			/* @var $loader TemplateLoader */
			if ($loaderClass === 'craft\app\web\twig\TemplateLoader')
			{
				$loader = new $loaderClass($this);
			}
			else
			{
				$loader = new $loaderClass();
			}

			$options = $this->_getTwigOptions();

			$twig = new Environment($loader, $options);

			$twig->addExtension(new \Twig_Extension_StringLoader());
			$twig->addExtension(new Extension($this));

			if (Craft::$app->getConfig()->get('devMode'))
			{
				$twig->addExtension(new \Twig_Extension_Debug());
			}

			// Set our timezone
			/** @var \Twig_Extension_Core $core */
			$core = $twig->getExtension('core');
			$timezone = Craft::$app->getTimeZone();
			$core->setTimezone($timezone);

			// Give plugins a chance to add their own Twig extensions
			$this->_addPluginTwigExtensions($twig);

			$this->_twigs[$loaderClass] = $twig;
		}

		return $this->_twigs[$loaderClass];
	}

	/**
	 * Returns whether a template is currently being rendered.
	 *
	 * @return bool Whether a template is currently being rendered.
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
		if ($this->getIsRenderingTemplate())
		{
			if (strncmp($this->_renderingTemplate, 'string:', 7) === 0)
			{
				$template = $this->_renderingTemplate;
			}
			else
			{
				$template = $this->resolveTemplate($this->_renderingTemplate);

				if (!$template)
				{
					$template = rtrim(Craft::$app->getPath()->getTemplatesPath(), '/\\').'/'.$this->_renderingTemplate;
				}
			}

			return $template;
		}
	}

	/**
	 * Returns whether a page template is currently being rendered
	 */

	/**
	 * Renders a Twig template.
	 *
	 * @param mixed $template  The name of the template to load, or a StringTemplate object.
	 * @param array $variables The variables that should be available to the template.
	 * @return string the rendering result
	 * @throws InvalidParamException if the template doesn’t exist
	 */
	public function renderTemplate($template, $variables = [])
	{
		Craft::trace("Rendering template: $template", __METHOD__);

		// Render and return
		$renderingTemplate = $this->_renderingTemplate;
		$this->_renderingTemplate = $template;
		Craft::beginProfile($template, __METHOD__);

		try
		{
			$output = $this->getTwig()->render($template, $variables);
		}
		catch (\Twig_Error_Loader $e)
		{
			throw new InvalidParamException("The template does not exist, or isn’t readable: $template");
		}

		Craft::endProfile($template, __METHOD__);
		$this->_renderingTemplate = $renderingTemplate;

		return $output;
	}

	/**
	 * Renders a Twig template that represents an entire web page.
	 *
	 * @param mixed $template  The name of the template to load, or a StringTemplate object.
	 * @param array $variables The variables that should be available to the template.
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

		return $output;
	}

	/**
	 * Renders a template defined in a string.
	 *
	 * @param string $template  The source template string.
	 * @param array  $variables Any variables that should be available to the template.
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
	 * @param string $template The source template string.
	 * @param mixed  $object   The object that should be passed into the template.
	 *
	 * @return string The rendered template.
	 */
	public function renderObjectTemplate($template, $object)
	{
		// If there are no dynamic tags, just return the template
		if (!StringHelper::contains($template, '{'))
		{
			return $template;
		}

		// Get a Twig instance with the String template loader
		$twig = $this->getTwig('Twig_Loader_String');

		// Have we already parsed this template?
		if (!isset($this->_objectTemplates[$template]))
		{
			// Replace shortcut "{var}"s with "{{object.var}}"s, without affecting normal Twig tags
			$formattedTemplate = preg_replace('/(?<![\{\%])\{(?![\{\%])/', '{{object.', $template);
			$formattedTemplate = preg_replace('/(?<![\}\%])\}(?![\}\%])/', '|raw}}', $formattedTemplate);
			$this->_objectTemplates[$template] = $twig->loadTemplate($formattedTemplate);
		}

		// Temporarily disable strict variables if it's enabled
		$strictVariables = $twig->isStrictVariables();

		if ($strictVariables)
		{
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
		if ($strictVariables)
		{
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
	 * @return bool Whether the template exists.
	 */
	public function doesTemplateExist($name)
	{
		try
		{
			return ($this->resolveTemplate($name) !== false);
		}
		catch (\Twig_Error_Loader $e)
		{
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
	 * [defaultTemplateExtensions](http://buildwithcraft.com/docs/config-settings#defaultTemplateExtensions) and
	 * [indexTemplateFilenames](http://buildwithcraft.com/docs/config-settings#indexTemplateFilenames) config settings.
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
	 * The actual directory that those files will be searched for is whatever [[\craft\app\services\Path::getTemplatesPath()]]
	 * returns (probably craft/templates/ if it’s a front-end site request, and craft/app/templates/ if it’s a Control
	 * Panel request).
	 *
	 * If this is a front-end site request, a folder named after the current locale ID will be checked first.
	 *
	 * - craft/templates/LocaleID/...
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
	 *     - craft/templates/LocaleID/foo/bar
	 *     - craft/templates/LocaleID/foo/bar.html
	 *     - craft/templates/LocaleID/foo/bar.twig
	 *     - craft/templates/LocaleID/foo/bar/index.html
	 *     - craft/templates/LocaleID/foo/bar/index.twig
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

		// Get the latest template base path
		$templatesPath = rtrim(Craft::$app->getPath()->getTemplatesPath(), '/\\');

		$key = $templatesPath.':'.$name;

		// Is this template path already cached?
		if (isset($this->_templatePaths[$key]))
		{
			return $this->_templatePaths[$key];
		}

		// Validate the template name
		$this->_validateTemplateName($name);

		// Look for the template in the main templates folder
		$basePaths = [];

		// Should we be looking for a localized version of the template?
		$request = Craft::$app->getRequest();

		if (!$request->getIsConsoleRequest() && $request->getIsSiteRequest() && IOHelper::folderExists($templatesPath.'/'.Craft::$app->language))
		{
			$basePaths[] = $templatesPath.'/'.Craft::$app->language;
		}

		$basePaths[] = $templatesPath;

		foreach ($basePaths as $basePath)
		{
			if (($path = $this->_resolveTemplate($basePath, $name)) !== null)
			{
				return $this->_templatePaths[$key] = $path;
			}
		}

		// Otherwise maybe it's a plugin template?

		// Only attempt to match against a plugin's templates if this is a CP or action request.

		if (!$request->getIsConsoleRequest() && ($request->getIsCpRequest() || Craft::$app->getRequest()->getIsActionRequest()))
		{
			// Sanitize
			$name = StringHelper::convertToUtf8($name);

			$parts = array_filter(explode('/', $name));
			$pluginHandle = StringHelper::toLowerCase(array_shift($parts));

			if ($pluginHandle && ($plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle)) !== null)
			{
				// Get the template path for the plugin.
				$basePath = Craft::$app->getPath()->getPluginsPath().'/'.StringHelper::toLowerCase($plugin->getHandle()).'/templates';

				// Get the new template name to look for within the plugin's templates folder
				$tempName = implode('/', $parts);

				if (($path = $this->_resolveTemplate($basePath, $tempName)) !== null)
				{
					return $this->_templatePaths[$key] = $path;
				}
			}
		}

		return false;
	}

	/**
	 * Registers a CSS file from the resources/ folder.
	 *
	 * @param string $path The resource path for the CSS file to be registered.
	 * @param array $options The HTML attributes for the link tag. Please refer to [[Html::cssFile()]] for
	 * the supported options. The following options are specially handled and are not treated as HTML attributes:
	 *
	 * - `depends`: array, specifies the names of the asset bundles that this CSS file depends on.
	 *
	 * @param string $key The key that identifies the CSS script file. If null, it will use
	 * $url as the key. If two CSS files are registered with the same key, the latter
	 * will overwrite the former.
	 */
	public function registerCssResource($path, $options = [], $key = null)
	{
		$this->_registerResource($path, $options, $key, 'css');
	}

	/**
	 * Registers a JS file from the resources/ folder.
	 *
	 * @param string $path The resource path for the JS file to be registered.
	 * @param array $options the HTML attributes for the script tag. The following options are specially handled
	 * and are not treated as HTML attributes:
	 *
	 * - `depends`: array, specifies the names of the asset bundles that this JS file depends on.
	 * - `position`: specifies where the JS script tag should be inserted in a page. The possible values are:
	 *     * [[POS_HEAD]]: in the head section
	 *     * [[POS_BEGIN]]: at the beginning of the body section
	 *     * [[POS_END]]: at the end of the body section. This is the default value.
	 *
	 * Please refer to [[Html::jsFile()]] for other supported options.
	 *
	 * @param string $key the key that identifies the JS script file. If null, it will use
	 * $url as the key. If two JS files are registered with the same key, the latter
	 * will overwrite the former.
	 */
	public function registerJsResource($path, $options = [], $key = null)
	{
		$this->_registerResource($path, $options, $key, 'js');
	}

	/**
	 * Registers a hi-res CSS code block.
	 *
	 * @param string $css the CSS code block to be registered
	 * @param array $options the HTML attributes for the style tag.
	 * @param string $key the key that identifies the CSS code block. If null, it will use
	 * $css as the key. If two CSS code blocks are registered with the same key, the latter
	 * will overwrite the former.
	 */
	public function registerHiResCss($css, $options = [], $key = null)
	{
		$key = $key ?: md5($css);
		$this->hiResCss[$key] = Html::style($css, $options);
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
	 * @return null
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
	 * @param bool $scriptTag Whether the Javascript code should be wrapped in a `<script>` tag. Defaults to `true`.
	 *
	 * @return string|null|false Returns `false` if there isn’t an active Javascript buffer, `null` if there is an
	 *                           active buffer but no Javascript code was actually added to it, or a string of the
	 *                           included Javascript code if there was any.
	 */
	public function clearJsBuffer($scriptTag = true)
	{
		if (empty($this->_jsBuffers))
		{
			return false;
		}

		// Get the active queue
		$js = $this->js;

		// Set the active queue to the last one
		$this->js = array_pop($this->_jsBuffers);

		if ($scriptTag === true && !empty($js))
		{
			$lines = [];

			foreach ([self::POS_HEAD, self::POS_BEGIN, self::POS_END, self::POS_LOAD, self::POS_READY] as $pos)
			{
				if (!empty($js[$pos]))
				{
					$lines[] = Html::script(implode("\n", $js[$pos]), ['type' => 'text/javascript']);
				}
			}

			return empty($lines) ? '' : implode("\n", $lines);
		}
		else
		{
			return $js;
		}
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
	 * @return string the rendered content
	 */
	public function getHeadHtml($clear = true)
	{
		$html = $this->renderHeadHtml();

		if ($clear === true)
		{
			$this->metaTags = null;
			$this->linkTags = null;
			$this->css = null;
			$this->hiResCss = null;
			$this->cssFiles = null;
			unset($this->jsFiles[self::POS_HEAD], $this->js[self::POS_HEAD]);
		}

		return $html;
	}

	/**
	 * Returns the content to be inserted at the beginning of the body section.
	 *
	 * This includes:
	 *
	 * - JS code registered with [[registerJs()]] with the position set to [[POS_BEGIN]]
	 * - JS files registered with [[registerJsFile()]] with the position set to [[POS_BEGIN]]
	 *
	 * @param boolean $clear Whether the content should be cleared from the queue (default is true)
	 * @return string the rendered content
	 */
	public function getBodyBeginHtml($clear = true)
	{
		$html = $this->renderBodyBeginHtml();

		if ($clear === true)
		{
			unset($this->jsFiles[self::POS_BEGIN], $this->js[self::POS_BEGIN]);
		}

		return $html;
	}

	/**
	 * Returns the content to be inserted at the end of the body section.
	 *
	 * This includes:
	 *
	 * - JS code registered with [[registerJs()]] with the position set to [[POS_END]], [[POS_READY]], or [[POS_LOAD]]
	 * - JS files registered with [[registerJsFile()]] with the position set to [[POS_END]]
	 *
	 * @param boolean $ajaxMode whether the view is rendering in AJAX mode.
	 * If true, the JS scripts registered at [[POS_READY]] and [[POS_LOAD]] positions
	 * will be rendered at the end of the view like normal scripts.
	 * @param boolean $clear Whether the content should be cleared from the queue (default is true)
	 * @return string the rendered content
	 */
	public function getBodyEndHtml($ajaxMode, $clear = true)
	{
		$html = $this->renderBodyEndHtml($ajaxMode);

		if ($clear === true)
		{
			unset($this->jsFiles[self::POS_END], $this->js[self::POS_END], $this->js[self::POS_READY], $this->js[self::POS_LOAD]);
		}

		return $html;
	}

	/**
	 * Returns the HTML for the CSRF hidden input token.  Used for when the config setting
	 * [enableCsrfValidation](http://buildwithcraft.com/docs/config-settings#enableCsrfValidation) is set to true.
	 *
	 * @return string If 'enabledCsrfProtection' is enabled, the HTML for the hidden input, otherwise an empty string.
	 */
	public function getCsrfInput()
	{
		if (Craft::$app->getConfig()->get('enableCsrfProtection') === true)
		{
			return '<input type="hidden" name="'.Craft::$app->getConfig()->get('csrfTokenName').'" value="'.Craft::$app->getRequest()->getCsrfToken().'">';
		}

		return '';
	}

	/**
	 * Prepares translations for inclusion in the template, to be used by the JS.
	 *
	 * @return null
	 */
	public function includeTranslations()
	{
		$messages = func_get_args();

		foreach ($messages as $message)
		{
			if (!array_key_exists($message, $this->_translations))
			{
				$translation = Craft::t('app', $message);

				if ($translation != $message)
				{
					$this->_translations[$message] = $translation;
				}
				else
				{
					$this->_translations[$message] = null;
				}
			}
		}
	}

	/**
	 * Returns the translations prepared for inclusion by includeTranslations(), in JSON, and flushes out the
	 * translations queue.
	 *
	 * @return string A JSON-encoded array of source/translation message mappings.
	 *
	 * @todo Add a $json param that determines whether the returned array should be JSON-encoded (defaults to true).
	 */
	public function getTranslations()
	{
		$translations = JsonHelper::encode(array_filter($this->_translations));
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
	 * @return null
	 */
	public function setNamespace($namespace)
	{
		$this->_namespace = $namespace;
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
	 * @param string $html            The template with the inputs.
	 * @param string $namespace       The namespace. Defaults to the [[getNamespace() active namespace]].
	 * @param bool   $otherAttributes Whether id=, for=, etc., should also be namespaced. Defaults to `true`.
	 *
	 * @return string The HTML with namespaced input names.
	 */
	public function namespaceInputs($html, $namespace = null, $otherAttributes = true)
	{
		if ($namespace === null)
		{
			$namespace = $this->getNamespace();
		}

		if ($namespace)
		{
			// Protect the textarea content
			$this->_textareaMarkers = [];
			$html = preg_replace_callback('/(<textarea\b[^>]*>)(.*?)(<\/textarea>)/is', [$this, '_createTextareaMarker'], $html);

			// name= attributes
			$html = preg_replace('/(?<![\w\-])(name=(\'|"))([^\'"\[\]]+)([^\'"]*)\2/i', '$1'.$namespace.'[$3]$4$2', $html);

			// id= and for= attributes
			if ($otherAttributes)
			{
				$idNamespace = $this->formatInputId($namespace);
				$html = preg_replace('/(?<![\w\-])((id|for|list|data\-target|data\-reverse\-target|data\-target\-prefix)=(\'|")#?)([^\.\'"][^\'"]*)\3/i', '$1'.$idNamespace.'-$4$3', $html);
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
		if ($namespace === null)
		{
			$namespace = $this->getNamespace();
		}

		if ($namespace)
		{
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
		if ($namespace === null)
		{
			$namespace = $this->getNamespace();
		}

		if ($namespace)
		{
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
	 * @return null
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

		if (isset($this->_hooks[$hook]))
		{
			foreach ($this->_hooks[$hook] as $method)
			{
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
	 * @return null
	 */
	public function onPluginsLoaded(Event $event)
	{
		foreach ($this->_twigs as $twig)
		{
			$this->_addPluginTwigExtensions($twig);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function clear()
	{
		parent::clear();
		$this->hiResCss = null;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function renderHeadHtml()
	{
		$lines = [];
		$html = parent::renderHeadHtml();

		if (!empty($html))
		{
			$lines[] = $html;
		}

		if (!empty($this->hiResCss))
		{
			$lines[] = implode("\n", $this->hiResCss);
		}

		return empty($lines) ? '' : implode("\n", $lines);
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
		if (StringHelper::contains($name, "\0"))
		{
			throw new \Twig_Error_Loader(Craft::t('app', 'A template name cannot contain NUL bytes.'));
		}

		if (PathHelper::ensurePathIsContained($name) === false)
		{
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
		$basePath = rtrim(IOHelper::normalizePathSeparators($basePath), '/\\');
		$name = trim(IOHelper::normalizePathSeparators($name), '/');

		// Set the defaultTemplateExtensions and indexTemplateFilenames vars
		if (!isset($this->_defaultTemplateExtensions))
		{
			$request = Craft::$app->getRequest();

			if (!$request->getIsConsoleRequest() && $request->getIsCpRequest())
			{
				$this->_defaultTemplateExtensions = ['html', 'twig'];
				$this->_indexTemplateFilenames = ['index'];
			}
			else
			{
				$this->_defaultTemplateExtensions = Craft::$app->getConfig()->get('defaultTemplateExtensions');
				$this->_indexTemplateFilenames = Craft::$app->getConfig()->get('indexTemplateFilenames');
			}
		}

		// $name could be an empty string (e.g. to load the homepage template)
		if ($name)
		{
			// Maybe $name is already the full file path
			$testPath = $basePath.'/'.$name;

			if (IOHelper::fileExists($testPath))
			{
				return $testPath;
			}

			foreach ($this->_defaultTemplateExtensions as $extension)
			{
				$testPath = $basePath.'/'.$name.'.'.$extension;

				if (IOHelper::fileExists($testPath))
				{
					return $testPath;
				}
			}
		}

		foreach ($this->_indexTemplateFilenames as $filename)
		{
			foreach ($this->_defaultTemplateExtensions as $extension)
			{
				$testPath = $basePath.'/'.($name ? $name.'/' : '').$filename.'.'.$extension;

				if (IOHelper::fileExists($testPath))
				{
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
		if (!isset($this->_twigOptions))
		{
			$this->_twigOptions = [
				'base_template_class' => '\\craft\\app\\web\\twig\\Template',
				'cache'               => Craft::$app->getPath()->getCompiledTemplatesPath(),
				'auto_reload'         => true,
				'charset'             => Craft::$app->charset,
			];

			if (Craft::$app->getConfig()->get('devMode'))
			{
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
	 * @return null
	 */
	private function _addPluginTwigExtensions(\Twig_Environment $twig)
	{
		// Check if the Plugins service has been loaded yet
		$pluginsService = Craft::$app->getPlugins();
		$pluginsService->loadPlugins();

		// Could be that this is getting called in the middle of plugin loading, so check again
		if ($pluginsService->arePluginsLoaded())
		{
			$pluginExtensions = $pluginsService->call('addTwigExtension');

			try
			{
				foreach ($pluginExtensions as $extension)
				{
					// It's possible for a plugin to register multiple extensions.
					if (is_array($extension))
					{
						foreach ($extension as $innerExtension)
						{
							$twig->addExtension($innerExtension);
						}
					}
					else
					{
						$twig->addExtension($extension);
					}
				}
			}
			catch (\LogicException $e)
			{
				Craft::warning('Tried to register plugin-supplied Twig extensions, but Twig environment has already initialized its extensions.', __METHOD__);
				return;
			}
		}
		else
		{
			// Wait around for plugins to actually be loaded, then do it for all Twig environments that have been created.
			Event::on(Plugins::className(), Plugins::EVENT_AFTER_LOAD_PLUGINS, [$this, 'onPluginsLoaded']);
		}
	}

	/**
	 * Registers an asset bundle for a file in the resources/ folder.
	 *
	 * @param string $path
	 * @param array $options
	 * @param string $key
	 * @param string $kind
	 */
	private function _registerResource($path, $options, $key, $kind)
	{
		$key = $key ?: 'resource:'.$path;

		// Make AppAsset the default dependency
		$depends = (array) ArrayHelper::remove($options, 'depends', [
			AppAsset::className()
		]);

		$sourcePath = Craft::getAlias('@app/resources');

		// If the resource doesn't exist in craft/app/resources, check plugins' resources/ subfolders
		if (!IOHelper::fileExists($sourcePath.'/'.$path))
		{
			$pathParts = explode('/', $path);

			if (count($pathParts) > 1)
			{
				$pluginHandle = array_shift($pathParts);
				$pluginSourcePath = Craft::getAlias('@craft/plugins/'.$pluginHandle.'/resources');
				$pluginSubpath = implode('/', $pathParts);

				if (IOHelper::fileExists($pluginSourcePath.'/'.$pluginSubpath))
				{
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
	 * @return string
	 */
	private function _getCpElementHtml(&$context)
	{
		if (!isset($context['element']))
		{
			return;
		}

		/** @var ElementInterface|Element $element */
		$element = $context['element'];

		if (!isset($context['context']))
		{
			$context['context'] = 'index';
		}

		if (!isset($context['viewMode']))
		{
			$context['viewMode'] = 'table';
		}

		$thumbClass = 'elementthumb'.$element->id;
		$iconClass = 'elementicon'.$element->id;

		if ($context['viewMode'] == 'thumbs')
		{
			$thumbSize = 100;
			$iconSize = 90;
			$thumbSelectorPrefix = '.thumbsview ';
		}
		else
		{
			$thumbSize = 30;
			$iconSize = 20;
			$thumbSelectorPrefix = '';
		}

		$thumbUrl = $element->getThumbUrl($thumbSize);
		$iconUrl = null;

		if ($thumbUrl)
		{
			$this->registerCss($thumbSelectorPrefix.'.'.$thumbClass." { background-image: url('".$thumbUrl."'); }");
			$this->registerHiResCss($thumbSelectorPrefix.'.'.$thumbClass." { background-image: url('".$element->getThumbUrl($thumbSize * 2)."'); background-size: ".$thumbSize.'px; }');
		}
		else
		{
			$iconUrl = $element->getIconUrl($iconSize);

			if ($iconUrl)
			{
				$this->registerCss($thumbSelectorPrefix.'.'.$iconClass." { background-image: url('".$iconUrl."'); }");
				$this->registerHiResCss($thumbSelectorPrefix.'.'.$iconClass." { background-image: url('".$element->getIconUrl($iconSize * 2)."); background-size: ".$iconSize.'px; }');
			}
		}

		$html = '<div class="element';

		if ($context['context'] == 'field')
		{
			$html .= ' removable';
		}

		if ($thumbUrl)
		{
			$html .= ' hasthumb';
		}
		else if ($iconUrl)
		{
			$html .= ' hasicon';
		}

		$label = (string) $element;

		$html .= '" data-id="'.$element->id.'" data-locale="'.$element->locale.'" data-status="'.$element->getStatus().'" data-label="'.$label.'" data-url="'.$element->getUrl().'"';

		if ($element->level)
		{
			$html .= ' data-level="'.$element->level.'"';
		}

		$isEditable = ElementHelper::isElementEditable($element);

		if ($isEditable)
		{
			$html .= ' data-editable';
		}

		$html .= '>';

		if ($context['context'] == 'field' && isset($context['name']))
		{
			$html .= '<input type="hidden" name="'.$context['name'].'[]" value="'.$element->id.'">';
			$html .= '<a class="delete icon" title="'.Craft::t('app', 'Remove').'"></a> ';
		}

		if ($thumbUrl)
		{
			$html .= '<div class="elementthumb '.$thumbClass.'"></div> ';
		}
		else if ($iconUrl)
		{
			$html .= '<div class="elementicon '.$iconClass.'"></div> ';
		}

		$html .= '<div class="label">';

		if ($element::hasStatuses())
		{
			$html .= '<span class="status '.$element->getStatus().'"></span>';
		}

		$html .= '<span class="title">';

		if ($context['context'] == 'index' && ($cpEditUrl = $element->getCpEditUrl()))
		{
			$html .= HtmlHelper::encodeParams('<a href="{cpEditUrl}">{label}</a>', [
				'cpEditUrl' => $cpEditUrl,
				'label' => $label
			]);
		}
		else
		{
			$html .= HtmlHelper::encode($label);
		}

		$html .= '</span></div></div>';

		return $html;
	}
}
