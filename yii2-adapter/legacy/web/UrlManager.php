<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\base\ElementInterface;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlRule as CraftUrlRule;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Route\MatchedElement;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Url;
use Illuminate\Support\Facades\Log;
use yii\web\UrlRule as YiiUrlRule;
use function CraftCms\Cms\backTraceAsString;

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
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $config['showScriptName'] = !Cms::config()->omitScriptNameInUrls;

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
        if (app()->runningInConsole()) {
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
        $route[1] = $this->_routeParams = Arr::merge($route[1], $this->_routeParams);

        return $route;
    }

    /**
     * @inheritdoc
     */
    public function createUrl($params): string
    {
        if (!Craft::$app->getIsInitialized()) {
            Log::warning(__METHOD__ . "() was called before the application was fully initialized.\n" . "Stack trace:\n" . backTraceAsString(), [__METHOD__]);
        }

        $params = (array)$params;
        unset($params[$this->routeParam]);

        $route = trim($params[0], '/');
        unset($params[0]);

        return Url::actionUrl($route, $params, null, false);
    }

    /**
     * @inheritdoc
     */
    public function createAbsoluteUrl($params, $scheme = null): string
    {
        if (!Craft::$app->getIsInitialized()) {
            Log::warning(__METHOD__ . "() was called before the application was fully initialized.\n" . "Stack trace:\n" . backTraceAsString(), [__METHOD__]);
        }

        $params = (array)$params;
        unset($params[$this->routeParam]);

        $route = trim($params[0], '/');
        unset($params[0]);

        // Create the action URL manually here, so it doesn't get treated as a control panel request
        $path = Cms::config()->actionTrigger . '/' . $route;

        return Url::siteUrl($path, $params, $scheme);
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
            $this->_routeParams = Arr::merge($this->_routeParams, $params);
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
     * @deprecated 6.0.0 use {@see MatchedElement::get()} instead.
     */
    public function getMatchedElement(): ElementInterface|false
    {
        return MatchedElement::get();
    }

    /**
     * Sets the matched element for the request.
     *
     * @param ElementInterface|false|null $element
     * @since 3.2.3
     * @deprecated 6.0.0 use {@see MatchedElement::set()} instead.
     */
    public function setMatchedElement(ElementInterface|false|null $element): void
    {
        MatchedElement::set($element);
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

        if (app()->runningInConsole()) {
            return [];
        }

        // Load the config file rules
        if ($request->getIsCpRequest()) {
            $baseCpRoutesPath = Craft::$app->getBasePath() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'cproutes';
            /** @var array $rules */
            $rules = require $baseCpRoutesPath . DIRECTORY_SEPARATOR . 'common.php';

            $eventName = self::EVENT_REGISTER_CP_URL_RULES;
        } else {
            $rules = Craft::$app->getRoutes()->getConfigFileRoutes();
            $eventName = self::EVENT_REGISTER_SITE_URL_RULES;
        }

        if ($this->hasEventHandlers($eventName)) {
            $event = new RegisterUrlRulesEvent(['rules' => $rules]);
            $this->trigger($eventName, $event);
            $rules = $event->rules;
        }

        return array_filter($rules);
    }

    /**
     * Returns the request's route.
     *
     * @param Request $request
     * @return mixed
     */
    private function _getRequestRoute(Request $request): mixed
    {
        // Do we have a URL route that matches?
        if (($route = $this->_getMatchedUrlRoute($request)) !== false) {
            return $route;
        }

        return false;
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
        /** @var YiiUrlRule|object $rule */
        foreach ($this->rules as $rule) {
            $route = $rule->parseRequest($this, $request);

            if (app()->hasDebugModeEnabled()) {
                Log::debug(Json::encode([
                    'rule' => 'URL Rule: ' . (method_exists($rule, '__toString') ? $rule->__toString() : get_class($rule)),
                    'match' => $route !== false,
                    'parent' => null,
                ]), [__METHOD__]);
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
}
