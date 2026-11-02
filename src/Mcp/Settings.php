<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Mcp\Validation\EndpointRule;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\Volumes;
use Illuminate\Validation\Validator;

/**
 * @since 6.0.0
 */
class Settings extends Component
{
    /** Whether the authenticated MCP server is enabled. */
    public bool $enabled = false;

    /** The authenticated MCP server's app-relative path. */
    public string $route = '/mcp';

    /** The app-relative path that always requires authentication, so OAuth clients can discover it. */
    public string $authorizationRoute = '/mcp/auth';

    public bool $publicEnabled = false;

    public string $publicRoute = '/mcp';

    /** @var list<string> */
    public array $publicSiteHandles = [];

    /** @var list<string> */
    public array $publicSectionHandles = [];

    /** @var list<string> */
    public array $publicNestedEntryFieldHandles = [];

    /** @var list<string> */
    public array $publicVolumeHandles = [];

    /** @var list<string> */
    public array $publicUserGroupHandles = [];

    /** @var list<string> */
    public array $publicElementTypes = [];

    public bool $publicAllowDrafts = false;

    public bool $publicAllowRevisions = false;

    public bool $publicAllowInactive = false;

    /** @var list<string> */
    public array $publicTools = [];

    /** @var list<string> */
    public array $publicResources = [];

    /** @var list<string> */
    public array $publicResourceTemplates = [];

    /** @var list<string> */
    public array $publicPrompts = [];

    /** @return array<string, mixed> */
    public function getRules(): array
    {
        return [
            'enabled' => ['boolean'],
            'route' => ['required', 'string', 'max:255', new EndpointRule],
            'authorizationRoute' => ['required', 'string', 'max:255', new EndpointRule],
            'publicEnabled' => ['boolean'],
            'publicRoute' => ['required', 'string', 'max:255', new EndpointRule(public: true)],
            'publicSiteHandles' => ['array'],
            'publicSiteHandles.*' => ['string', 'max:512'],
            'publicSectionHandles' => ['array'],
            'publicSectionHandles.*' => ['string', 'max:512'],
            'publicNestedEntryFieldHandles' => ['array'],
            'publicNestedEntryFieldHandles.*' => ['string', 'max:512'],
            'publicVolumeHandles' => ['array'],
            'publicVolumeHandles.*' => ['string', 'max:512'],
            'publicUserGroupHandles' => ['array'],
            'publicUserGroupHandles.*' => ['string', 'max:512'],
            'publicElementTypes' => ['array'],
            'publicElementTypes.*' => ['string', 'max:512'],
            'publicAllowDrafts' => ['boolean'],
            'publicAllowRevisions' => ['boolean'],
            'publicAllowInactive' => ['boolean'],
            'publicTools' => ['array'],
            'publicTools.*' => ['string', 'max:512'],
            'publicResources' => ['array'],
            'publicResources.*' => ['string', 'max:512'],
            'publicResourceTemplates' => ['array'],
            'publicResourceTemplates.*' => ['string', 'max:512'],
            'publicPrompts' => ['array'],
            'publicPrompts.*' => ['string', 'max:512'],
        ];
    }

    public function afterValidate(?Validator $validator = null): void
    {
        if (! Cms::isInstalled()) {
            return;
        }

        $this->validateHandles($this->publicSiteHandles, 'publicSiteHandles', static fn (string $handle): mixed => Sites::getSiteByHandle($handle, true));
        $this->validateHandles($this->publicSectionHandles, 'publicSectionHandles', Sections::getSectionByHandle(...));
        $this->validateHandles($this->publicNestedEntryFieldHandles, 'publicNestedEntryFieldHandles', $this->nestedEntryFieldByHandle(...));
        $this->validateHandles($this->publicVolumeHandles, 'publicVolumeHandles', Volumes::getVolumeByHandle(...));
        $this->validateHandles($this->publicUserGroupHandles, 'publicUserGroupHandles', static fn (string $handle): mixed => UserGroups::getGroupByHandle($handle));
        $this->validateHandles($this->publicElementTypes, 'publicElementTypes', $this->elementTypeByName(...));
    }

    /** @return array<string, string> */
    public function attributeLabels(): array
    {
        return [
            'route' => 'MCP endpoint',
            'authorizationRoute' => 'MCP authorization endpoint',
            'publicRoute' => 'Public MCP endpoint',
            'publicSiteHandles' => 'Public site handles',
            'publicSectionHandles' => 'Public section handles',
            'publicNestedEntryFieldHandles' => 'Public nested entry field handles',
            'publicVolumeHandles' => 'Public volume handles',
            'publicUserGroupHandles' => 'Public user group handles',
            'publicElementTypes' => 'Public element types',
        ];
    }

    /** Whether a route begins with the control panel trigger. */
    public function isCpRoute(string $route): bool
    {
        $cpTrigger = trim((string) Cms::config()->cpTrigger, '/');

        return $cpTrigger !== '' && str_starts_with(trim($route, '/').'/', $cpTrigger.'/');
    }

    /** Whether both servers are enabled and share an endpoint. */
    public function sharesEndpoint(): bool
    {
        return $this->enabled && $this->publicEnabled && trim($this->route, '/') === trim($this->publicRoute, '/');
    }

    /**
     * Returns the absolute URL for a route.
     *
     * Clients discover OAuth endpoints from these URLs, so they come from the configured base URLs rather than the
     * request's host.
     */
    public function url(string $route): string
    {
        return rtrim($this->baseUrl($route), '/').'/'.trim($route, '/');
    }

    /** Returns the configured base URL for a route, without its control panel trigger. */
    public function baseUrl(string $route): string
    {
        $baseUrl = $this->isCpRoute($route) ? Env::parse(Cms::config()->baseCpUrl) : null;

        return (string) ($baseUrl ?? config('app.url'));
    }

    private function nestedEntryFieldByHandle(string $handle): mixed
    {
        $field = Fields::getFieldByHandle($handle);

        if (! $field instanceof ElementContainerFieldInterface) {
            return null;
        }

        return Fields::getNestedEntryFieldTypes()->contains($field::class) ? $field : null;
    }

    /** @return class-string|null */
    private function elementTypeByName(string $name): ?string
    {
        return app(PublicElementTypes::class)->resolve($name);
    }

    /**
     * @param  list<string>  $handles
     * @param  callable(string): mixed  $resolver
     */
    private function validateHandles(array $handles, string $attribute, callable $resolver): void
    {
        foreach ($handles as $handle) {
            if ($resolver($handle)) {
                continue;
            }

            $label = $this->getAttributeLabel($attribute);
            $this->errors()->add($attribute, "{$label} contains an unknown handle [{$handle}].");
        }
    }
}
