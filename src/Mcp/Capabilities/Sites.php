<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Sites as SiteService;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Typecast;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ResourceReadException;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * @since 6.0.0
 */
readonly class Sites
{
    private const array AttributesSchema = [
        'name' => ['type' => 'string', 'description' => 'Site name.'],
        'handle' => ['type' => 'string', 'description' => 'Site handle.'],
        'language' => ['type' => 'string', 'description' => 'Site language, such as en-US.'],
        'baseUrl' => ['type' => ['string', 'null'], 'description' => 'Site base URL or alias.'],
        'groupId' => ['type' => 'integer', 'description' => 'Existing site group ID.'],
        'primary' => ['type' => 'boolean', 'description' => 'Whether this is the primary site.'],
        'enabled' => ['type' => ['boolean', 'string'], 'description' => 'Whether the site is enabled.'],
        'hasUrls' => ['type' => 'boolean', 'description' => 'Whether the site has URLs.'],
        'sortOrder' => ['type' => 'integer', 'description' => 'Site sort order.'],
    ];

    public function __construct(private SiteService $sites) {}

    /** @return array{count: int, sites: list<array<string, mixed>>} */
    #[McpTool(
        name: 'sites.list',
        description: 'Lists Craft CMS sites.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function list(): array
    {
        $sites = $this->sites
            ->getAllSites()
            ->map($this->serialize(...))
            ->values();

        return [
            'count' => $sites->count(),
            'sites' => $sites->all(),
        ];
    }

    /**
     * @param  int|null  $id  Site ID.
     * @param  string|null  $uid  Site UID.
     * @param  string|null  $handle  Site handle.
     * @return array{site: array<string, mixed>}
     */
    #[McpTool(
        name: 'sites.get',
        description: 'Gets a Craft CMS site by ID, UID, or handle.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function get(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
    ): array {
        if (count(Arr::whereNotNull([$id, $uid, $handle])) !== 1) {
            throw new ToolCallException('Provide exactly one of: id, uid, handle.');
        }

        $site = $this->find($id, $uid, $handle);

        if (! $site) {
            throw new ToolCallException('Site not found.');
        }

        return ['site' => $this->serialize($site)];
    }

    /**
     * @param  array<string, mixed>  $attributes  Native Craft site attributes.
     * @return array{site: array<string, mixed>}
     */
    #[McpTool(name: 'sites.create', description: 'Creates a Craft CMS site.')]
    #[RequiresAdminChanges]
    public function create(
        #[Schema(
            type: 'object',
            properties: self::AttributesSchema,
            additionalProperties: false,
        )]
        array $attributes = [],
    ): array {
        $site = Typecast::configure(new Site, $attributes);

        if (! $this->sites->saveSite($site)) {
            throw new ToolCallException($this->validationErrors($site));
        }

        return ['site' => $this->serialize($site)];
    }

    /**
     * @param  int|null  $id  Site ID.
     * @param  string|null  $uid  Site UID.
     * @param  string|null  $handle  Site handle.
     * @param  array<string, mixed>  $attributes  Native Craft site attributes to update.
     * @return array{site: array<string, mixed>}
     */
    #[McpTool(
        name: 'sites.update',
        description: 'Updates a Craft CMS site.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function update(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
        #[Schema(
            type: 'object',
            properties: self::AttributesSchema,
            additionalProperties: false,
        )]
        array $attributes = [],
    ): array {
        $site = $this->find($id, $uid, $handle);

        if (! $site) {
            throw new ToolCallException('Site not found.');
        }

        Typecast::configure($site, $attributes);

        if (! $this->sites->saveSite($site)) {
            throw new ToolCallException($this->validationErrors($site));
        }

        return ['site' => $this->serialize($site)];
    }

    /**
     * @param  int|null  $id  Site ID.
     * @param  string|null  $uid  Site UID.
     * @param  string|null  $handle  Site handle.
     * @param  int|null  $transferContentTo  Site ID to transfer content to.
     * @return array{deleted: true}
     */
    #[McpTool(
        name: 'sites.delete',
        description: 'Deletes a Craft CMS site.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function delete(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
        ?int $transferContentTo = null,
    ): array {
        $site = $this->find($id, $uid, $handle);

        if (! $site) {
            throw new ToolCallException('Site not found.');
        }

        if (! $this->sites->deleteSite($site, $transferContentTo)) {
            throw new ToolCallException('Site could not be deleted.');
        }

        return ['deleted' => true];
    }

    /** @return array{count: int, sites: list<array<string, mixed>>} */
    #[McpResource(
        uri: 'craft://sites',
        name: 'craft-sites',
        title: 'Craft Sites',
        description: 'A JSON list of Craft CMS sites.',
        mimeType: 'application/json',
    )]
    #[RequiresAdmin]
    public function resource(): array
    {
        return $this->list();
    }

    /** @return array{site: array<string, mixed>} */
    #[McpResourceTemplate(
        uriTemplate: 'craft://sites/{site}',
        name: 'craft-sites-get',
        title: 'Craft Site',
        description: 'A JSON Craft CMS site record addressed by site ID, UID, or handle.',
        mimeType: 'application/json',
    )]
    #[RequiresAdmin]
    public function resourceByIdentifier(string $site): array
    {
        $resolved = match (true) {
            ctype_digit($site) => $this->find(id: (int) $site),
            Str::isUuid($site) => $this->find(uid: $site),
            default => $this->find(handle: $site),
        };

        if (! $resolved) {
            throw new ResourceReadException('Site not found.');
        }

        return ['site' => $this->serialize($resolved)];
    }

    private function find(?int $id = null, ?string $uid = null, ?string $handle = null): ?Site
    {
        return $this->sites
            ->getAllSites()
            ->first(fn (Site $site): bool => match (true) {
                $id !== null => $site->id === $id,
                $uid !== null => $site->uid === $uid,
                $handle !== null => $site->handle === $handle,
                default => false,
            });
    }

    /** @return array<string, mixed> */
    private function serialize(Site $site): array
    {
        return [
            'id' => $site->id,
            'uid' => $site->uid,
            'handle' => $site->handle,
            'name' => $site->getName(),
            'language' => $site->getLanguage(),
            'primary' => $site->primary,
            'enabled' => $site->getEnabled(),
            'hasUrls' => $site->hasUrls,
            'baseUrl' => $site->getBaseUrl(),
        ];
    }

    private function validationErrors(Site $site): string
    {
        return implode("\n", $site->errors()->all()) ?: 'Site could not be saved.';
    }
}
