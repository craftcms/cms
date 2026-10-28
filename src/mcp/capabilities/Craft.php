<?php

declare(strict_types=1);

namespace craft\mcp\capabilities;

use Craft as CraftApp;
use craft\mcp\AdminInstructions;
use craft\mcp\attributes\PublicMcp;
use craft\mcp\public\Access;
use craft\mcp\public\ElementCriteria;
use craft\mcp\public\ElementQuery;
use craft\mcp\public\ElementType;
use craft\mcp\PublicElementTypes;
use craft\mcp\serializers\ElementSerializer;
use craft\models\Section;
use craft\models\Site;
use craft\models\UserGroup;
use craft\models\Volume;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;

/**
 * @since 5.12.0
 */
class Craft
{
    private const CriteriaSchema = [
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
    ) {
    }

    /** @return array{version: string, edition: string, name: string, isInstalled: bool, siteCount: ?int, instructions: string} */
    #[McpTool(
        name: 'info.get',
        description: 'Returns Craft application information and full admin MCP instructions, including site and plugin guidance. Read these instructions before using other Craft capabilities.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function info(): array
    {
        $isInstalled = CraftApp::$app->getIsInstalled();

        return [
            'version' => CraftApp::$app->getVersion(),
            'edition' => CraftApp::$app->edition->handle(),
            'name' => CraftApp::$app->getSystemName(),
            'isInstalled' => $isInstalled,
            'siteCount' => $isInstalled ? CraftApp::$app->getSites()->getTotalSites() : null,
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
            ->map(static fn(ElementType $type): string => $type->name)
            ->values()
            ->all();

        return [
            'queryTypes' => $queryTypes,
            'criteria' => collect($queryTypes)
                ->mapWithKeys(fn(string $name): array => [$name => $this->criteria->for($this->elementTypes->make($name))])
                ->all(),
            'sites' => collect($this->access->models('sites'))->map($this->serializeSite(...))->all(),
            'sections' => collect($this->access->models('sections'))->map($this->serializeSection(...))->all(),
            'volumes' => collect($this->access->models('volumes'))->map($this->serializeVolume(...))->all(),
            'userGroups' => collect($this->access->models('userGroups'))->map($this->serializeUserGroup(...))->all(),
            'elementTypes' => collect($this->access->elementTypes())
                ->map(fn(string $name): array => $this->elementTypes->make($name)->context())
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
            'type' => $section->type,
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
