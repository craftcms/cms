<?php

namespace craft\services;

use Craft;
use craft\elements\User;
use craft\events\RegisterComponentTypesEvent;
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

    /** @since 5.12.0 */
    public const SCOPE_READ = 'mcp:read';
    /** @since 5.12.0 */
    public const SCOPE_WRITE = 'mcp:write';
    /** @since 5.12.0 */
    public const SCOPE_ADMIN = 'mcp:admin';

    /**
     * @var string[]|null The OAuth scopes granted to the access token that authenticated the current request, or
     * `null` if no token authenticated it.
     * @since 5.12.0
     */
    public ?array $tokenScopes = null;

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
            'scopes' => [
                self::SCOPE_READ => Craft::t('app', 'View content you have access to.'),
                self::SCOPE_WRITE => Craft::t('app', 'View, create, edit, and delete content you have access to.'),
                self::SCOPE_ADMIN => Craft::t('app', 'Everything you can do, including admin-only tools such as changing settings.'),
            ],
            'defaultScopes' => [self::SCOPE_READ, self::SCOPE_WRITE, self::SCOPE_ADMIN],
            'scopeLevels' => [self::SCOPE_READ, self::SCOPE_WRITE, self::SCOPE_ADMIN],
            'scopeLabels' => [
                self::SCOPE_READ => Craft::t('app', 'Read only'),
                self::SCOPE_WRITE => Craft::t('app', 'Read and write'),
                self::SCOPE_ADMIN => Craft::t('app', 'Full access'),
            ],
            'userScopes' => fn(User $user, array $scopes) => $user->admin ? $scopes : array_diff($scopes, [self::SCOPE_ADMIN]),
            'resourceUrl' => fn() => $this->getSettings()->url($this->getSettings()->authorizationRoute),
            'consentDescription' => 'This client can use Craft MCP at the access level you choose, within your permissions.',
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
