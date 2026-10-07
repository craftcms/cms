<?php

namespace craft\services;

use Craft;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\mcp\CapabilityContainer;
use craft\mcp\CapabilityRegistry;
use craft\mcp\contentmodel\CheckRegistry;
use craft\mcp\elements\ElementAdapterRegistry;
use craft\mcp\oauth\OAuth;
use craft\mcp\Settings;
use craft\web\UrlManager;
use yii\base\Component;
use yii\base\Event;

/** MCP services and plugin extension registries. @since 5.12.0 */
class Mcp extends Component
{
    /**
     * @event RegisterComponentTypesEvent Registers SDK-attributed MCP capability classes.
     * @since 5.12.0
     */
    public const EVENT_REGISTER_CAPABILITY_TYPES = 'registerCapabilityTypes';

    private ?Settings $settings = null;
    private ?CapabilityRegistry $capabilities = null;
    private ?ElementAdapterRegistry $adapters = null;
    private ?CheckRegistry $checks = null;
    private ?CapabilityContainer $container = null;

    public function init(): void
    {
        parent::init();

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $endpoint = trim(Craft::$app->getConfig()->getMcp()->endpoint, '/');
            $event->rules = [
                ...$this->metadataRules(),
                $endpoint => 'mcp/admin',
                "$endpoint/oauth/<action:register|authorize|approve|deny|token>" => 'mcp-oauth/<action>',
                'settings/mcp' => 'mcp-settings/index',
                ...$event->rules,
            ];
        });

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $rules = $this->metadataRules();

            if (Craft::$app->getIsInstalled() && $this->getSettings()->publicEnabled) {
                $rules[trim($this->getSettings()->publicRoute, '/')] = 'mcp/public';
            }

            $event->rules = [...$rules, ...$event->rules];
        });
    }

    private function metadataRules(): array
    {
        $path = trim(parse_url((new OAuth())->resource(), PHP_URL_PATH) ?? '', '/');

        return [
            '.well-known/oauth-protected-resource/' . $path => 'mcp-oauth/resource',
            '.well-known/oauth-authorization-server/' . $path => 'mcp-oauth/metadata',
        ];
    }

    public function getSettings(): Settings
    {
        return $this->settings ??= new Settings(Craft::$app->getProjectConfig()->get('mcp') ?? []);
    }

    public function resetSettings(): void
    {
        $this->settings = null;
    }

    public function getCapabilityRegistry(): CapabilityRegistry
    {
        if ($this->capabilities === null) {
            $event = new RegisterComponentTypesEvent();
            $this->trigger(self::EVENT_REGISTER_CAPABILITY_TYPES, $event);
            $this->capabilities = new CapabilityRegistry($event->types);
        }

        return $this->capabilities;
    }

    public function getElementAdapterRegistry(): ElementAdapterRegistry
    {
        return $this->adapters ??= new ElementAdapterRegistry();
    }

    public function getCheckRegistry(): CheckRegistry
    {
        return $this->checks ??= new CheckRegistry();
    }

    public function getCapabilityContainer(): CapabilityContainer
    {
        return $this->container ??= new CapabilityContainer();
    }
}
