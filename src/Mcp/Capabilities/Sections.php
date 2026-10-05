<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\DefaultPlacement;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Sections as SectionService;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Typecast;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ResourceReadException;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;

/**
 * @since 6.0.0
 */
readonly class Sections
{
    private const array EntryTypesSchema = [
        'anyOf' => [
            ['type' => 'integer'],
            ['type' => 'string'],
            ['type' => 'object'],
        ],
        'description' => 'Existing entry type ID, UID, or usage config containing id or uid.',
    ];

    private const array SiteSettingsSchema = [
        'type' => 'object',
        'properties' => [
            'siteId' => ['type' => 'integer', 'description' => 'Site ID.'],
            'enabledByDefault' => ['type' => 'boolean', 'description' => 'Whether entries are enabled by default.'],
            'hasUrls' => ['type' => 'boolean', 'description' => 'Whether entries have URLs on this site.'],
            'uriFormat' => ['type' => ['string', 'null'], 'description' => 'URI format for entries on this site.'],
            'template' => ['type' => ['string', 'null'], 'description' => 'Template path.'],
        ],
        'required' => ['siteId'],
        'additionalProperties' => false,
    ];

    private const array PreviewTargetsSchema = [
        'type' => 'object',
        'properties' => [
            'label' => ['type' => 'string', 'description' => 'Preview target label.'],
            'urlFormat' => ['type' => 'string', 'description' => 'Preview target URL format.'],
        ],
        'required' => ['label', 'urlFormat'],
        'additionalProperties' => false,
    ];

    public function __construct(private SectionService $sections) {}

    /** @return array{count: int, sections: list<array<string, mixed>>} */
    #[McpTool(
        name: 'sections.list',
        description: 'Lists Craft CMS sections.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function list(): array
    {
        $sections = $this->sections
            ->getAllSections()
            ->map($this->serialize(...))
            ->values();

        return [
            'count' => $sections->count(),
            'sections' => $sections->all(),
        ];
    }

    /**
     * @param  int|null  $id  Section ID.
     * @param  string|null  $uid  Section UID.
     * @param  string|null  $handle  Section handle.
     * @return array{section: array<string, mixed>}
     */
    #[McpTool(
        name: 'sections.get',
        description: 'Gets a Craft CMS section by ID, UID, or handle.',
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

        $section = $this->find($id, $uid, $handle);

        if (! $section) {
            throw new ToolCallException('Section not found.');
        }

        return ['section' => $this->serialize($section)];
    }

    /**
     * @param  list<int|string|array<string, mixed>>  $entryTypes  Existing entry type references or usage configs.
     * @param  list<array<string, mixed>>  $siteSettings  Per-site section settings.
     * @param  list<array{label: string, urlFormat: string}>|null  $previewTargets  Preview target configs.
     * @return array{section: array<string, mixed>}
     */
    #[McpTool(name: 'sections.create', description: 'Creates a Craft CMS section.')]
    #[RequiresAdminChanges]
    public function create(
        string $name,
        string $handle,
        SectionType $type = SectionType::Channel,
        #[Schema(items: self::EntryTypesSchema)]
        array $entryTypes = [],
        #[Schema(items: self::SiteSettingsSchema)]
        array $siteSettings = [],
        #[Schema(minimum: 0)]
        int $minAuthors = 1,
        #[Schema(minimum: 1)]
        ?int $maxAuthors = 1,
        #[Schema(minimum: 1)]
        ?int $maxLevels = null,
        bool $enableVersioning = true,
        PropagationMethod $propagationMethod = PropagationMethod::All,
        DefaultPlacement $defaultPlacement = DefaultPlacement::End,
        #[Schema(items: self::PreviewTargetsSchema)]
        ?array $previewTargets = null,
    ): array {
        $section = new Section([
            'name' => $name,
            'handle' => $handle,
            'type' => $type,
            'entryTypes' => $entryTypes,
            'siteSettings' => $this->siteSettings($siteSettings),
            'minAuthors' => $minAuthors,
            'maxAuthors' => $maxAuthors,
            'maxLevels' => $maxLevels,
            'enableVersioning' => $enableVersioning,
            'propagationMethod' => $propagationMethod,
            'defaultPlacement' => $defaultPlacement,
            'previewTargets' => $previewTargets,
        ]);

        if (! $this->sections->saveSection($section)) {
            throw new ToolCallException($this->validationErrors($section));
        }

        return ['section' => $this->serialize($section)];
    }

    /**
     * @param  int|null  $id  Section ID.
     * @param  string|null  $uid  Section UID.
     * @param  string|null  $currentHandle  Existing section handle.
     * @param  list<int|string|array<string, mixed>>  $entryTypes  Existing entry type references or usage configs.
     * @param  list<array<string, mixed>>  $siteSettings  Per-site section settings.
     * @param  list<array{label: string, urlFormat: string}>|null  $previewTargets  Preview target configs.
     * @return array{section: array<string, mixed>}
     */
    #[McpTool(
        name: 'sections.update',
        description: 'Updates a Craft CMS section.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function update(
        RequestContext $context,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $currentHandle = null,
        ?string $name = null,
        ?string $handle = null,
        SectionType $type = SectionType::Channel,
        #[Schema(items: self::EntryTypesSchema)]
        array $entryTypes = [],
        #[Schema(items: self::SiteSettingsSchema)]
        array $siteSettings = [],
        #[Schema(minimum: 0)]
        int $minAuthors = 1,
        #[Schema(minimum: 1)]
        ?int $maxAuthors = null,
        #[Schema(minimum: 1)]
        ?int $maxLevels = null,
        bool $enableVersioning = true,
        PropagationMethod $propagationMethod = PropagationMethod::All,
        DefaultPlacement $defaultPlacement = DefaultPlacement::End,
        #[Schema(items: self::PreviewTargetsSchema)]
        ?array $previewTargets = null,
    ): array {
        $section = $this->find($id, $uid, $currentHandle);

        if (! $section) {
            throw new ToolCallException('Section not found.');
        }

        $request = $context->getRequest();
        assert($request instanceof CallToolRequest);

        Typecast::configure($section, array_intersect_key([
            'name' => $name,
            'handle' => $handle,
            'type' => $type,
            'entryTypes' => $entryTypes,
            'siteSettings' => $this->siteSettings($siteSettings),
            'minAuthors' => $minAuthors,
            'maxAuthors' => $maxAuthors,
            'maxLevels' => $maxLevels,
            'enableVersioning' => $enableVersioning,
            'propagationMethod' => $propagationMethod,
            'defaultPlacement' => $defaultPlacement,
            'previewTargets' => $previewTargets,
        ], $request->arguments));

        if (! $this->sections->saveSection($section)) {
            throw new ToolCallException($this->validationErrors($section));
        }

        return ['section' => $this->serialize($section)];
    }

    /**
     * @param  int|null  $id  Section ID.
     * @param  string|null  $uid  Section UID.
     * @param  string|null  $handle  Section handle.
     * @return array{deleted: true}
     */
    #[McpTool(
        name: 'sections.delete',
        description: 'Deletes a Craft CMS section.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function delete(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
    ): array {
        $section = $this->find($id, $uid, $handle);

        if (! $section) {
            throw new ToolCallException('Section not found.');
        }

        if (! $this->sections->deleteSection($section)) {
            throw new ToolCallException('Section could not be deleted.');
        }

        return ['deleted' => true];
    }

    /** @return array{count: int, sections: list<array<string, mixed>>} */
    #[McpResource(
        uri: 'craft://sections',
        name: 'craft-sections',
        title: 'Craft Sections',
        description: 'A JSON list of Craft CMS sections.',
        mimeType: 'application/json',
    )]
    #[RequiresAdmin]
    public function resource(): array
    {
        return $this->list();
    }

    /** @return array{section: array<string, mixed>} */
    #[McpResourceTemplate(
        uriTemplate: 'craft://sections/{section}',
        name: 'craft-sections-get',
        title: 'Craft Section',
        description: 'A JSON Craft CMS section record addressed by section ID, UID, or handle.',
        mimeType: 'application/json',
    )]
    #[RequiresAdmin]
    public function resourceByIdentifier(string $section): array
    {
        $resolved = match (true) {
            ctype_digit($section) => $this->find(id: (int) $section),
            Str::isUuid($section) => $this->find(uid: $section),
            default => $this->find(handle: $section),
        };

        if (! $resolved) {
            throw new ResourceReadException('Section not found.');
        }

        return ['section' => $this->serialize($resolved)];
    }

    private function find(?int $id = null, ?string $uid = null, ?string $handle = null): ?Section
    {
        return match (true) {
            $id !== null => $this->sections->getSectionById($id),
            $uid !== null => $this->sections->getSectionByUid($uid),
            $handle !== null => $this->sections->getSectionByHandle($handle),
            default => null,
        };
    }

    /**
     * @param  list<array<string, mixed>>  $settings
     * @return list<SectionSiteSettings>
     */
    private function siteSettings(array $settings): array
    {
        return array_map(
            static fn (array $settings): SectionSiteSettings => new SectionSiteSettings($settings),
            $settings,
        );
    }

    /** @return array<string, mixed> */
    private function serialize(Section $section): array
    {
        $config = $section->getConfig();
        $structure = $config['structure'] ?? null;

        unset($config['structure']);

        return [
            'id' => $section->id,
            'uid' => $section->uid,
            ...$config,
            'maxLevels' => is_array($structure) ? $structure['maxLevels'] ?? null : null,
            'siteSettings' => array_values(array_map(
                static fn (SectionSiteSettings $settings): array => [
                    'siteId' => $settings->siteId,
                    'enabledByDefault' => $settings->enabledByDefault,
                    'hasUrls' => $settings->hasUrls,
                    'uriFormat' => $settings->uriFormat,
                    'template' => $settings->template,
                ],
                $section->getSiteSettings(),
            )),
        ];
    }

    private function validationErrors(Section $section): string
    {
        return implode("\n", $section->errors()->all()) ?: 'Section could not be saved.';
    }
}
