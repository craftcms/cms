<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Mcp\AdminInstructions;
use CraftCms\Cms\Mcp\Attributes\PublicMcp;
use CraftCms\Cms\Mcp\Public\Access;
use CraftCms\Cms\Mcp\Public\ElementCriteria;
use CraftCms\Cms\Mcp\Public\ElementQuery;
use CraftCms\Cms\Mcp\Public\ElementType;
use CraftCms\Cms\Mcp\PublicElementTypes;
use CraftCms\Cms\Mcp\Serializers\ElementSerializer;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Data\UserGroup;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;

/**
 * @since 6.0.0
 */
readonly class Craft
{
    private const array CriteriaSchema = [
        'type' => 'object',
        'description' => 'ElementQuery criteria for the selected type. Use craft-context-get to discover supported criteria and public constraints.',
        'additionalProperties' => true,
    ];

    public function __construct(
        private Access $access,
        private ElementCriteria $criteria,
        private ElementQuery $query,
        private PublicElementTypes $elementTypes,
        private AdminInstructions $instructions,
    ) {}

    /** @return array{version: string, edition: string, name: string, isInstalled: bool, siteCount: ?int, instructions: string} */
    #[McpTool(
        name: 'info.get',
        description: 'Returns Craft application information and full admin MCP instructions, including site and plugin guidance. Read these instructions before using other Craft capabilities.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function info(): array
    {
        $isInstalled = Cms::isInstalled();

        return [
            'version' => Cms::version(),
            'edition' => Edition::get()->handle(),
            'name' => Cms::systemName(),
            'isInstalled' => $isInstalled,
            'siteCount' => $isInstalled ? Sites::getTotalSites() : null,
            'instructions' => $this->instructions->get(),
        ];
    }

    /** @return array<string, mixed> */
    #[McpTool(
        name: 'craft-context-get',
        title: 'Get the public Craft query context',
        description: 'Returns public Craft MCP query types, criteria, sites, sections, volumes, user groups, and element types.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[PublicMcp]
    public function context(): array
    {
        $queryTypes = collect($this->elementTypes->all())
            ->keys()
            ->map($this->elementTypes->make(...))
            ->filter($this->query->allows(...))
            ->map(static fn (ElementType $type): string => $type->name)
            ->values()
            ->all();

        return [
            'queryTypes' => $queryTypes,
            'criteria' => collect($queryTypes)
                ->mapWithKeys(fn (string $name): array => [$name => $this->criteria->for($this->elementTypes->make($name))])
                ->all(),
            'sites' => collect($this->access->models('sites'))->map($this->serializeSite(...))->all(),
            'sections' => collect($this->access->models('sections'))->map($this->serializeSection(...))->all(),
            'volumes' => collect($this->access->models('volumes'))->map($this->serializeVolume(...))->all(),
            'userGroups' => collect($this->access->models('userGroups'))->map($this->serializeUserGroup(...))->all(),
            'elementTypes' => collect($this->access->elementTypes())
                ->map(fn (string $name): array => $this->elementTypes->make($name)->context())
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $criteria  Native Craft ElementQuery criteria. Use craft-context-get for supported values.
     * @param  list<string>|null  $fields
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'craft-query',
        title: 'Query approved public Craft content',
        description: 'Queries allowed public Craft element types using ElementQuery criteria.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[PublicMcp]
    public function query(
        string $type,
        #[Schema(definition: self::CriteriaSchema)]
        array $criteria = [],
        #[Schema(definition: ElementSerializer::FieldsSchema)]
        ?array $fields = [],
    ): array {
        return $this->query->query($type, $criteria, $fields);
    }

    /** @return array<string, mixed> */
    private function serializeSite(Site $site): array
    {
        return [
            'id' => $site->id,
            'uid' => $site->uid,
            'handle' => $site->handle,
            'name' => $site->getName(),
            'language' => $site->language,
            'primary' => $site->primary,
        ];
    }

    /** @return array<string, mixed> */
    private function serializeSection(Section $section): array
    {
        return [
            'id' => $section->id,
            'uid' => $section->uid,
            'handle' => $section->handle,
            'name' => $section->name,
            'type' => $section->type?->value,
        ];
    }

    /** @return array<string, mixed> */
    private function serializeVolume(Volume $volume): array
    {
        return [
            'id' => $volume->id,
            'uid' => $volume->uid,
            'handle' => $volume->handle,
            'name' => $volume->name,
        ];
    }

    /** @return array<string, mixed> */
    private function serializeUserGroup(UserGroup $group): array
    {
        return [
            'id' => $group->id,
            'uid' => $group->uid,
            'handle' => $group->handle,
            'name' => $group->name,
        ];
    }
}
