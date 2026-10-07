<?php

namespace craft\services;

use Craft;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\UrlHelper;
use craft\mcp\CapabilityContainer;
use craft\mcp\CapabilityRegistry;
use craft\mcp\contentmodel\CheckRegistry;
use craft\mcp\elements\ElementAdapterRegistry;
use craft\mcp\Settings;
use craft\oauth\Resource;
use yii\base\Component;

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

    /** Returns MCP's protected OAuth resource definition. @since 5.12.0 */
    public function getOAuthResource(): Resource
    {
        return new Resource([
            'id' => 'mcp',
            'scopes' => ['mcp:use' => 'Use Craft MCP'],
            'defaultScopes' => ['mcp:use'],
            'permissions' => ['accessCp', 'useCraftMcp'],
            'resourceUrl' => fn() => UrlHelper::removeParam(UrlHelper::cpUrl('mcp'), 'site'),
            'consentDescription' => 'This client can use Craft MCP to read and change content and settings within your permissions.',
        ]);
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
