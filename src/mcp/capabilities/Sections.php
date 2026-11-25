<?php

declare(strict_types=1);

namespace craft\mcp\capabilities;

use Craft;
use craft\enums\PropagationMethod;
use craft\helpers\StringHelper;
use craft\mcp\attributes\RequiresAdmin;
use craft\mcp\attributes\RequiresAdminChanges;
use craft\models\Section;
use craft\models\Section_SiteSettings as SectionSiteSettings;
use craft\services\Entries as SectionService;
use Illuminate\Support\Arr;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ResourceReadException;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Server\RequestContext;

/**
 * @since 5.12.0
 */
class Sections
{
    private const EntryTypesSchema = [
        'anyOf' => [
            ['type' => 'integer'],
            ['type' => 'string'],
            ['type' => 'object'],
        ],
        'description' => 'Existing entry type ID, UID, or usage config containing id or uid.',
    ];

    private const SiteSettingsSchema = [
        'type' => 'object',
        'properties' => [
            'siteId' => ['type' => 'integer'],
            'enabledByDefault' => ['type' => 'boolean', 'description' => 'Whether entries are enabled by default.'],
            'hasUrls' => ['type' => 'boolean', 'description' => 'Whether entries have URLs on this site.'],
            'uriFormat' => ['type' => ['string', 'null'], 'description' => 'URI format for entries on this site.'],
            'template' => ['type' => ['string', 'null'], 'description' => 'Template path.'],
        ],
        'required' => ['siteId'],
        'additionalProperties' => false,
    ];

    private const PreviewTargetsSchema = [
        'type' => 'object',
        'properties' => [
            'label' => ['type' => 'string', 'description' => 'Preview target label.'],
            'urlFormat' => ['type' => 'string', 'description' => 'Preview target URL format.'],
        ],
        'required' => ['label', 'urlFormat'],
        'additionalProperties' => false,
    ];

    public function __construct(private SectionService $sections)
    {
    }

    /** @return array{count: int, sections: list<array<string, mixed>>} */
    #[RequiresAdmin]
    public function list(): array
    {
        $sections = collect($this->sections->getAllSections())
            ->map($this->serialize(...))
            ->values();

        return [
            'count' => $sections->count(),
            'sections' => $sections->all(),
        ];
    }

    /** @return array{section: array<string, mixed>} */
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

        if (!$section) {
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
    #[RequiresAdminChanges]
    public function create(
        string $name,
        string $handle,
        #[Schema(enum: ['single', 'channel', 'structure'])]
        string $type = 'channel',
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
        #[Schema(enum: ['beginning', 'end'])]
        string $defaultPlacement = 'end',
        #[Schema(items: self::PreviewTargetsSchema)]
        ?array $previewTargets = null,
    ): array {
        $section = new Section([
            'name' => $name,
            'handle' => $handle,
            'type' => $type,
            'siteSettings' => $this->siteSettings($siteSettings),
            'minAuthors' => $minAuthors,
            'maxAuthors' => $maxAuthors,
            'maxLevels' => $maxLevels,
            'enableVersioning' => $enableVersioning,
            'propagationMethod' => $propagationMethod,
            'defaultPlacement' => $defaultPlacement,
            'previewTargets' => $previewTargets,
        ]);
        $section->setEntryTypes($entryTypes);

        if (!$this->sections->saveSection($section)) {
            throw new ToolCallException($this->validationErrors($section));
        }

        return ['section' => $this->serialize($section)];
    }

    /**
     * @param  string|null  $currentHandle  Existing section handle.
     * @param  list<int|string|array<string, mixed>>  $entryTypes  Existing entry type references or usage configs.
     * @param  list<array<string, mixed>>  $siteSettings  Per-site section settings.
     * @param  list<array{label: string, urlFormat: string}>|null  $previewTargets  Preview target configs; null restores the default target.
     * @return array{section: array<string, mixed>}
     */
    #[RequiresAdminChanges]
    public function update(
        RequestContext $context,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $currentHandle = null,
        ?string $name = null,
        ?string $handle = null,
        #[Schema(enum: ['single', 'channel', 'structure'])]
        string $type = 'channel',
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
        #[Schema(enum: ['beginning', 'end'])]
        string $defaultPlacement = 'end',
        #[Schema(items: self::PreviewTargetsSchema)]
        ?array $previewTargets = null,
    ): array {
        $section = $this->find($id, $uid, $currentHandle);

        if (!$section) {
            throw new ToolCallException('Section not found.');
        }

        $request = $context->getRequest();
        assert($request instanceof CallToolRequest);

        Craft::configure($section, array_intersect_key([
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
            'previewTargets' => $previewTargets ?? (new Section())->previewTargets,
        ], $request->arguments));

        if (!$this->sections->saveSection($section)) {
            throw new ToolCallException($this->validationErrors($section));
        }

        return ['section' => $this->serialize($section)];
    }

    /** @return array{deleted: true} */
    #[RequiresAdminChanges]
    public function delete(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
    ): array {
        $section = $this->find($id, $uid, $handle);

        if (!$section) {
            throw new ToolCallException('Section not found.');
        }

        if (!$this->sections->deleteSection($section)) {
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
            StringHelper::isUUID($section) => $this->find(uid: $section),
            default => $this->find(handle: $section),
        };

        if (!$resolved) {
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
            static fn(array $settings): SectionSiteSettings => new SectionSiteSettings($settings),
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
                static fn(SectionSiteSettings $settings): array => [
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
        return implode("\n", $section->getErrorSummary(true)) ?: 'Section could not be saved.';
    }
}
