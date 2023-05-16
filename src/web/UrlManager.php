<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use craft\web\UrlRule as CraftUrlRule;
use yii\web\UrlRule as YiiUrlRule;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UrlManager extends \yii\web\UrlManager
{
    /**
     * @event RegisterUrlRulesEvent The event that is triggered when registering
     * URL rules for the control panel.
     *
     * ::: warning
     * This event gets called during class initialization, so you should always
     * use a class-level event handler.
     * :::
     *
     * ---
     * ```php
     * use craft\events\RegisterUrlRulesEvent;
     * use craft\web\UrlManager;
     * use yii\base\Event;
     * Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $e) {
     *     $e->rules['foo'] = 'bar/baz';
     * });
     * ```
     */
    public const EVENT_REGISTER_CP_URL_RULES = 'registerCpUrlRules';

    /**
     * @event RegisterUrlRulesEvent The event that is triggered when registering
     * URL rules for the front-end site.
     *
     * ::: warning
     * This event gets called during class initialization, so you should always
     * use a class-level event handler.
     * :::
     *
     * ---
     * ```php
     * use craft\events\RegisterUrlRulesEvent;
     * use craft\web\UrlManager;
     * use yii\base\Event;
     * Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $e) {
     *     $e->rules['foo'] = 'bar/baz';
     * });
     * ```
     */
    public const EVENT_REGISTER_SITE_URL_RULES = 'registerSiteUrlRules';

    /**
     * @var bool Whether [[parseRequest()]] should check for a token on the request and route the request based on that.
     * @since 3.2.0
     */
    public bool $checkToken = true;

    /**
     * @var bool whether the full list of URL rules have been defined
     * @see parseRequest()
     */
    private bool $_definedRules = false;

    /**
     * @var array Params that should be included in the
     */
    private array $_routeParams = [];

    /**
     * @var ElementInterface|false|null
     */
    private null|false|ElementInterface $_matchedElement = null;

    /**
     * @var mixed
     */
    private mixed $_matchedElementRoute = null;

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $config['showScriptName'] = !Craft::$app->getConfig()->getGeneral()->omitScriptNameInUrls;

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function parseRequest($request)
    {
        // Now we can define the full list of rules
        if (!$this->_definedRules) {
            $this->addRules($this->_getRules());
            $this->_definedRules = true;
        }

        /** @var Request $request */
        // Just in case...
        if ($request->getIsConsoleRequest()) {
            return false;
        }

        if (($route = $this->_getRequestRoute($request)) === false) {
            return false;
        }

        // Make sure there's a params array
        if (!isset($route[1])) {
            $route[1] = [];
        }

        // Merge in any additional route params
        $route[1] = $this->_routeParams = ArrayHelper::merge($route[1], $this->_routeParams);

        return $route;
    }

    /**
     * @inheritdoc
     */
    public function createUrl($params): string
    {
        if (!Craft::$app->getIsInitialized()) {
            Craft::warning(__METHOD__ . "() was called before the application was fully initialized.\n" .
                "Stack trace:\n" . App::backtrace(), __METHOD__);
        }

        $params = (array)$params;
        unset($params[$this->routeParam]);

        $route = trim($params[0], '/');
        unset($params[0]);

        return UrlHelper::actionUrl($route, $params, null, false);
    }

    /**
     * @inheritdoc
     */
    public function createAbsoluteUrl($params, $scheme = null): string
    {
        if (!Craft::$app->getIsInitialized()) {
            Craft::warning(__METHOD__ . "() was called before the application was fully initialized.\n" .
                "Stack trace:\n" . App::backtrace(), __METHOD__);
        }

        $params = (array)$params;
        unset($params[$this->routeParam]);

        $route = trim($params[0], '/');
        unset($params[0]);

        // Create the action URL manually here, so it doesn't get treated as a control panel request
        $path = Craft::$app->getConfig()->getGeneral()->actionTrigger . '/' . $route;

        return UrlHelper::siteUrl($path, $params, $scheme);
    }

    /**
     * Returns the route params, or null if we haven't parsed the URL yet.
     *
     * @return array|null
     */
    public function getRouteParams(): ?array
    {
        return $this->_routeParams;
    }

    /**
     * Sets params to be passed to the routed controller action.
     *
     * @param array $params The route params
     * @param bool $merge Whether these params should be merged with existing params
     */
    public function setRouteParams(array $params, bool $merge = true): void
    {
        if ($merge) {
            $this->_routeParams = ArrayHelper::merge($this->_routeParams, $params);
        } else {
            $this->_routeParams = $params;
        }
    }

    /**
     * Returns the element that was matched by the URI.
     *
     * ::: warning
     * This should only be called once the application has been fully initialized.
     * Otherwise some plugins may be unable to register [[EVENT_REGISTER_CP_URL_RULES]]
     * and [[EVENT_REGISTER_SITE_URL_RULES]] event handlers successfully.
     * :::
     *
     * ---
     * ```php
     * use craft\web\Application;
     *
     * Craft::$app->on(Application::EVENT_INIT, function() {
     *     $element = Craft::$app->urlManager->getMatchedElement();
     * }
     * ```
     *
     * @return ElementInterface|false
     */
    public function getMatchedElement(): ElementInterface|false
    {
        if (!Craft::$app->getIsInitialized()) {
            Craft::warning(__METHOD__ . "() was called before the application was fully initialized.\n" .
                "Stack trace:\n" . App::backtrace(), __METHOD__);
        }

        if (isset($this->_matchedElement)) {
            return $this->_matchedElement;
        }

        $request = Craft::$app->getRequest();

        if ($request->getIsConsoleRequest()) {
            return false;
        }

        $this->_getMatchedElementRoute($request);
        return $this->_matchedElement;
    }

    /**
     * Sets the matched element for the request.
     *
     * @param ElementInterface|false|null $element
     * @since 3.2.3
     */
    public function setMatchedElement(ElementInterface|false|null $element): void
    {
        if ($element instanceof ElementInterface) {
            if ($route = $element->getRoute()) {
                if (is_string($route)) {
                    $route = [$route, []];
                }
                $this->_matchedElement = $element;
                $this->_matchedElementRoute = $route;
                return;
            }

            // Element doesn't have a route so ignore it
            $element = false;
        }

        $this->_matchedElement = $element;
        $this->_matchedElementRoute = $element;
    }

    /**
     * @inheritdoc
     */
    protected function buildRules($ruleDeclarations): array
    {
        // Add support for patterns in keys even if the value is an array
        $i = 0;
        $verbs = 'GET|HEAD|POST|PUT|PATCH|DELETE|OPTIONS';

        foreach ($ruleDeclarations as $key => $rule) {
            if (is_string($key) && is_array($rule)) {
                // Code adapted from \yii\web\UrlManager::init()
                if (
                    !isset($rule['verb']) &&
                    preg_match("/^((?:($verbs),)*($verbs))\\s+(.*)$/", $key, $matches)
                ) {
                    $rule['verb'] = explode(',', $matches[1]);

                    if (!isset($rule['mode']) && !in_array('GET', $rule['verb'], true)) {
                        $rule['mode'] = YiiUrlRule::PARSING_ONLY;
                    }

                    $key = $matches[4];
                }

                $rule['pattern'] = $key;
                array_splice($ruleDeclarations, $i, 1, [$rule]);
            }

            $i++;
        }

        return parent::buildRules($ruleDeclarations);
    }

    /**
     * Returns the rules that should be used for the current request.
     *
     * @return array
     */
    private function _getRules(): array
    {
        $request = Craft::$app->getRequest();

        if ($request->getIsConsoleRequest()) {
            return [];
        }

        // Load the config file rules
        if ($request->getIsCpRequest()) {
            $baseCpRoutesPath = Craft::$app->getBasePath() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'cproutes';
            /** @var array $rules */
            $rules = require $baseCpRoutesPath . DIRECTORY_SEPARATOR . 'common.php';

            if (Craft::$app->getEdition() === Craft::Pro) {
                $rules = array_merge($rules, require $baseCpRoutesPath . DIRECTORY_SEPARATOR . 'pro.php');
            }

            $eventName = self::EVENT_REGISTER_CP_URL_RULES;
        } else {
            $routesService = Craft::$app->getRoutes();

            $rules = array_merge(
                $routesService->getConfigFileRoutes(),
                $routesService->getProjectConfigRoutes()
            );

            $eventName = self::EVENT_REGISTER_SITE_URL_RULES;
        }

        $event = new RegisterUrlRulesEvent([
            'rules' => $rules,
        ]);
        $this->trigger($eventName, $event);

        return array_filter($event->rules);
    }

    /**
     * Returns the request's route.
     *
     * @param Request $request
     * @return mixed
     */
    private function _getRequestRoute(Request $request): mixed
    {
        // Is there a token in the URL?
        if (($route = $this->_getTokenRoute($request)) !== false) {
            return $route;
        }

        // Is this an element request?
        if (($route = $this->_getMatchedElementRoute($request)) !== false) {
            return $route;
        }

        // Do we have a URL route that matches?
        if (($route = $this->_getMatchedUrlRoute($request)) !== false) {
            return $route;
        }

        // Is this a "well-known" request?
        if (($route = $this->_getMatchedDiscoverableUrlRoute($request)) !== false) {
            return $route;
        }

        // Does it look like they're trying to access a public template path?
        return $this->_getTemplateRoute($request);
    }

    /**
     * Attempts to match a path with an element in the database.
     *
     * @param Request $request
     * @return mixed
     */
    private function _getMatchedElementRoute(Request $request): mixed
    {
        if (isset($this->_matchedElementRoute)) {
            return $this->_matchedElementRoute;
        }

        if (
            !Craft::$app->getIsInstalled() ||
            !$request->getIsSiteRequest() ||
            Craft::$app->getConfig()->getGeneral()->headlessMode
        ) {
            $this->setMatchedElement(false);
            return false;
        }

        $path = $request->getPathInfo();

        // Don't allow routing to the homepage via /__home__
        if ($path !== Element::HOMEPAGE_URI) {
            $element = Craft::$app->getElements()->getElementByUri($path, Craft::$app->getSites()->getCurrentSite()->id, true);
        } else {
            $element = null;
        }

        $this->setMatchedElement($element ?: false);

        if (App::devMode()) {
            Craft::debug([
                'rule' => 'Element URI: ' . $path,
                'match' => $this->_matchedElement instanceof ElementInterface,
                'parent' => null,
            ], __METHOD__);
        }

        return $this->_matchedElementRoute;
    }

    /**
     * Attempts to match a path with the registered URL routes.
     *
     * @param Request $request
     * @return array|false
     */
    private function _getMatchedUrlRoute(Request $request): array|false
    {
        // Code adapted from \yii\web\UrlManager::parseRequest()
        /** @var YiiUrlRule $rule */
        foreach ($this->rules as $rule) {
            $route = $rule->parseRequest($this, $request);

            if (App::devMode()) {
                Craft::debug([
                    'rule' => 'URL Rule: ' . (method_exists($rule, '__toString') ? $rule->__toString() : get_class($rule)),
                    'match' => $route !== false,
                    'parent' => null,
                ], __METHOD__);
            }

            if ($route !== false) {
                if ($rule instanceof CraftUrlRule && $rule->params) {
                    $this->setRouteParams($rule->params);
                }

                return $route;
            }
        }

        return false;
    }

    /**
     * Attempts to match a path with a “well-known” URL.
     *
     * @param Request $request
     * @return array|false
     */
    private function _getMatchedDiscoverableUrlRoute(Request $request): array|false
    {
        $redirectUri = $request->getPathInfo() === '.well-known/change-password'
            ? Craft::$app->getConfig()->getGeneral()->getSetPasswordRequestPath(Craft::$app->getSites()->getCurrentSite()->handle)
            : null;

        if (App::devMode()) {
            Craft::debug([
                'rule' => 'Discoverable change password URL',
                'match' => $redirectUri !== null,
                'parent' => null,
            ], __METHOD__);
        }

        if (!$redirectUri) {
            return false;
        }

        return [
            'redirect',
            [
                'url' => Craft::$app->getSecurity()->hashData($redirectUri),
                'statusCode' => 302,
            ],
        ];
    }

    /**
     * Returns whether the current path is "public" (no segments that start with the privateTemplateTrigger).
     *
     * @param Request $request
     * @return bool
     */
    private function _isPublicTemplatePath(Request $request): bool
    {
        if ($request->getIsSiteRequest() && !Craft::$app->getConfig()->getGeneral()->privateTemplateTrigger) {
            // If privateTemplateTrigger is set to an empty value, disable all public template routing
            return false;
        }

        return Craft::$app->getView()->doesTemplateExist($request->getPathInfo(), publicOnly: true);
    }

    /**
     * Checks if the path could be a public template path and if so, returns a route to that template.
     *
     * @param Request $request
     * @return array|false
     */
    private function _getTemplateRoute(Request $request): array|false
    {
        if ($request->getIsSiteRequest() && Craft::$app->getConfig()->getGeneral()->headlessMode) {
            return false;
        }

        $matches = $this->_isPublicTemplatePath($request);
        $path = $request->getPathInfo();

        if (App::devMode()) {
            Craft::debug([
                'rule' => 'Template: ' . $path,
                'match' => $matches,
                'parent' => null,
            ], __METHOD__);
        }

        if (!$matches) {
            return false;
        }

        return ['templates/render', ['template' => $path]];
    }

    /**
     * Checks if the request has a token in it.
     *
     * @param Request $request
     * @return array|false
     */
    private function _getTokenRoute(Request $request): array|false
    {
        if (!$this->checkToken) {
            return false;
        }

        $token = $request->getToken();

        if (App::devMode()) {
            Craft::debug([
                'rule' => 'Token' . ($token !== null ? ': ' . $token : ''),
                'match' => $token !== null,
                'parent' => null,
            ], __METHOD__);
        }

        if ($token === null) {
            return false;
        }

        return Craft::$app->getTokens()->getTokenRoute($token);
    }
}
