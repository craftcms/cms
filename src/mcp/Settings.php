<?php

namespace craft\mcp;

use Craft;
use craft\base\ElementContainerFieldInterface;
use craft\base\Model;
use craft\helpers\UrlHelper;
use craft\services\ProjectConfig;
use craft\web\Request;

/** MCP server settings and public approvals, stored in project config. @since 5.12.0 */
class Settings extends Model
{
    /** Whether the authenticated MCP server is enabled. */
    public bool $enabled = false;
    /** The authenticated MCP server's app-relative path. */
    public string $route = '/mcp';
    /** The app-relative path that always requires authentication, so OAuth clients can discover it. */
    public string $authorizationRoute = '/mcp/auth';
    public bool $publicEnabled = false;
    public string $publicRoute = '/mcp';
    public array $publicSiteHandles = [];
    public array $publicSectionHandles = [];
    public array $publicNestedEntryFieldHandles = [];
    public array $publicVolumeHandles = [];
    public array $publicUserGroupHandles = [];
    public array $publicElementTypes = [];
    public bool $publicAllowDrafts = false;
    public bool $publicAllowRevisions = false;
    public bool $publicAllowInactive = false;
    public array $publicTools = [];
    public array $publicResources = [];
    public array $publicResourceTemplates = [];
    public array $publicPrompts = [];

    protected function defineRules(): array
    {
        return [
            [['enabled', 'publicEnabled', 'publicAllowDrafts', 'publicAllowRevisions', 'publicAllowInactive'], 'boolean'],
            [['route', 'authorizationRoute', 'publicRoute'], 'required'],
            [['route', 'authorizationRoute', 'publicRoute'], 'string', 'max' => 255],
            [['route', 'authorizationRoute', 'publicRoute'], 'validateRoute'],
            [[...$this->scopeAttributes(), 'publicTools', 'publicResources', 'publicResourceTemplates', 'publicPrompts'], 'each', 'rule' => ['string', 'max' => 512]],
            [$this->scopeAttributes(), 'validateScopes'],
        ];
    }

    /**
     * Returns the route for a request to one of the enabled servers' paths, or `false`.
     *
     * Requests to a path both servers share go to the authenticated server when they send credentials, or when a
     * browser requests its setup page.
     */
    public function requestRoute(Request $request): array|false
    {
        // Control panel requests match their full path, so authenticated routes can begin with the control panel
        // trigger, or live on the control panel’s own domain.
        $path = $request->getIsCpRequest() ? $request->getFullPath() : $request->getPathInfo();

        // Discoverable servers serve their Server Card at `<server-url>/server-card`, per SEP-2127.
        if (!$request->getIsCpRequest() && str_ends_with($path, '/server-card') && isset($this->discoverableServers()[substr($path, 0, -strlen('/server-card'))])) {
            return ['mcp/server-card', []];
        }

        $authenticated = $this->enabled && in_array($path, [trim($this->route, '/'), trim($this->authorizationRoute, '/')], true);
        $public = $this->publicEnabled && !$request->getIsCpRequest() && $path === trim($this->publicRoute, '/');

        if ($authenticated && (!$public || $request->getIsGet() || $request->getHeaders()->has('Authorization'))) {
            return ['mcp/authenticated', []];
        }

        return $public ? ['mcp/public', []] : false;
    }

    /** Whether a route begins with the control panel trigger. */
    public function isCpRoute(string $route): bool
    {
        $cpTrigger = Craft::$app->getConfig()->getGeneral()->cpTrigger;
        return $cpTrigger && str_starts_with(trim($route, '/') . '/', $cpTrigger . '/');
    }

    /**
     * Returns the servers that `/.well-known/ai-catalog.json` advertises, as app-relative paths mapped to whether
     * each is the public server.
     *
     * Servers on control panel URLs are never advertised, and the authenticated server is only advertised when
     * people can register for an account on the front end.
     */
    public function discoverableServers(): array
    {
        $servers = [];

        if ($this->publicEnabled && !$this->routesToCp($this->publicRoute)) {
            $servers[trim($this->publicRoute, '/')] = true;
        }

        if (
            $this->enabled &&
            Craft::$app->getProjectConfig()->get(ProjectConfig::PATH_USERS . '.allowPublicRegistration') &&
            !$this->routesToCp($this->authorizationRoute)
        ) {
            $servers[trim($this->authorizationRoute, '/')] ??= false;
        }

        return $servers;
    }

    /** Whether a request to a route’s URL would be a control panel request, following craft\web\Request::init(). */
    private function routesToCp(string $route): bool
    {
        if ($this->isCpRoute($route)) {
            return true;
        }

        $general = Craft::$app->getConfig()->getGeneral();
        if (!$general->baseCpUrl) {
            return false;
        }

        // Requests go to the control panel when its base URL matches them more closely than the site’s does
        $url = UrlHelper::siteUrl(trim($route, '/'));
        $site = Craft::$app->getSites()->getCurrentSite();
        $siteScore = ($site->getBaseUrl() ? $this->scoreBaseUrl($site->getBaseUrl(), $url) : 0) + ($site->primary ? 1 : 0);
        $cpBaseUrl = implode('/', array_filter([rtrim($general->baseCpUrl, '/'), $general->cpTrigger]));

        return $this->scoreBaseUrl($cpBaseUrl, $url) > $siteScore;
    }

    /** Scores how closely a base URL matches a URL, the way craft\web\Request scores site and control panel base URLs. */
    private function scoreBaseUrl(string $baseUrl, string $url): int
    {
        $base = parse_url($baseUrl) ?: [];
        $target = parse_url($url) ?: [];

        if (!empty($base['host']) && strcasecmp($base['host'], $target['host'] ?? '') !== 0) {
            return 0;
        }

        $basePath = preg_replace('/\/\/+/', '/', trim($base['path'] ?? '', '/'));
        if ($basePath !== '' && !str_starts_with(trim($target['path'] ?? '', '/') . '/', "$basePath/")) {
            return 0;
        }

        $scheme = strtolower($target['scheme'] ?? 'https');
        $baseScheme = strtolower($base['scheme'] ?? $scheme);
        $port = $target['port'] ?? ($scheme === 'https' ? 443 : 80);
        $basePort = $base['port'] ?? ($baseScheme === 'https' ? 443 : 80);

        return 1000 + strlen($basePath) * 100 + ($basePort == $port ? 100 : 0) + ($baseScheme === $scheme ? 10 : 0);
    }

    /**
     * Returns the absolute URL for a route.
     *
     * Without a control panel trigger, the control panel’s own domain serves the authenticated routes too, so
     * control panel requests get URLs on that domain.
     */
    public function url(string $route): string
    {
        $path = trim($route, '/');
        $general = Craft::$app->getConfig()->getGeneral();
        $request = Craft::$app->getRequest();

        if ($this->isCpRoute($path)) {
            $path = ltrim(substr($path, strlen($general->cpTrigger)), '/');
            return UrlHelper::removeParam(UrlHelper::cpUrl($path), 'site');
        }

        if (!$general->cpTrigger && $request->getIsCpRequest()) {
            // Routes include the control panel base URL’s path, like control panel requests’ full paths, so remove it
            // the way craft\web\Request::init() does.
            $basePath = trim((string)parse_url((string)$general->baseCpUrl, PHP_URL_PATH), '/');
            $requestBasePath = trim($request->getBaseUrl(), '/');
            if ($requestBasePath !== '' && str_starts_with("$basePath/", "$requestBasePath/")) {
                $basePath = ltrim(substr($basePath, strlen($requestBasePath)), '/');
            }
            if ($basePath !== '' && str_starts_with("$path/", "$basePath/")) {
                $path = ltrim(substr($path, strlen($basePath)), '/');
            }
            return UrlHelper::removeParam(UrlHelper::cpUrl($path), 'site');
        }

        return UrlHelper::siteUrl($path);
    }

    public function validateRoute(string $attribute): void
    {
        $path = trim($this->$attribute, '/');
        $general = Craft::$app->getConfig()->getGeneral();
        $reserved = array_filter([$general->actionTrigger, '.well-known']);

        if ($path === '' || !preg_match('~^[a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_-]+)*$~', $path) || in_array(explode('/', $path)[0], $reserved, true)) {
            $this->addError($attribute, Craft::t('app', 'Choose a nonempty path outside the action and well-known routes.'));
            return;
        }

        if ($this->isCpRoute($path)) {
            if ($attribute === 'publicRoute') {
                $this->addError($attribute, Craft::t('app', 'Public MCP can’t be available within the control panel.'));
            }
            return;
        }

        foreach (array_keys(Craft::$app->getRoutes()->getConfigFileRoutes()) as $route) {
            $route = preg_replace('/^(?:GET|POST|PUT|PATCH|DELETE|OPTIONS|HEAD)(?:,[A-Z]+)*\s+/', '', $route);

            if (trim($route, '/') === $path) {
                $this->addError($attribute, Craft::t('app', 'This path is already used by a route.'));
                return;
            }
        }

        foreach (Craft::$app->getRoutes()->getProjectConfigRoutes() as $route) {
            if (($route['pattern'] ?? null) === $path) {
                $this->addError($attribute, Craft::t('app', 'This path is already used by a route.'));
            }
        }
    }

    public function validateScopes(string $attribute): void
    {
        foreach ($this->$attribute as $handle) {
            $model = match ($attribute) {
                'publicSiteHandles' => Craft::$app->getSites()->getSiteByHandle($handle, true),
                'publicSectionHandles' => Craft::$app->getEntries()->getSectionByHandle($handle),
                'publicNestedEntryFieldHandles' => Craft::$app->getFields()->getFieldByHandle($handle),
                'publicVolumeHandles' => Craft::$app->getVolumes()->getVolumeByHandle($handle),
                'publicUserGroupHandles' => Craft::$app->getUserGroups()->getGroupByHandle($handle),
                'publicElementTypes' => Craft::$app->get('mcp')->getCapabilityContainer()->get(PublicElementTypes::class)->resolve($handle),
                default => throw new \InvalidArgumentException("Unknown public scope [$attribute]."),
            };

            if (!$model || ($attribute === 'publicNestedEntryFieldHandles' && (!$model instanceof ElementContainerFieldInterface || !in_array($model::class, Craft::$app->getFields()->getNestedEntryFieldTypes(), true)))) {
                $this->addError($attribute, Craft::t('app', 'Unknown public content source: {handle}', ['handle' => $handle]));
            }
        }
    }

    private function scopeAttributes(): array
    {
        return ['publicSiteHandles', 'publicSectionHandles', 'publicNestedEntryFieldHandles', 'publicVolumeHandles', 'publicUserGroupHandles', 'publicElementTypes'];
    }
}
