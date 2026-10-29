<?php

namespace craft\mcp;

use Craft;
use craft\base\ElementContainerFieldInterface;
use craft\base\Model;
use craft\helpers\UrlHelper;
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

    /** Returns the absolute URL for a route. */
    public function url(string $route): string
    {
        $path = trim($route, '/');

        if ($this->isCpRoute($path)) {
            $path = ltrim(substr($path, strlen(Craft::$app->getConfig()->getGeneral()->cpTrigger)), '/');
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
