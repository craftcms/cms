<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use Carbon\CarbonImmutable;
use Closure;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Models\Draft;
use CraftCms\Cms\Mcp\Attributes\RequiresPermission;
use CraftCms\Cms\Mcp\ElementQueryCriteria;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Mcp\Serializers\ReleaseSerializer;
use CraftCms\Cms\Release\Models\Release;
use CraftCms\Cms\Release\Releases as ReleaseService;
use CraftCms\Cms\Workflow\Exceptions\WorkflowException;
use Illuminate\Support\Facades\Validator;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** @since 6.0.0 */
readonly class Releases
{
    private const array MetadataSchema = [
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255],
            'description' => ['type' => ['string', 'null']],
            'plannedAt' => ['type' => ['string', 'null'], 'format' => 'date-time'],
        ],
        'additionalProperties' => false,
    ];

    private const array CriteriaSchema = [
        'type' => 'object',
        'properties' => [
            'search' => ['type' => 'string', 'description' => 'Searches release names.'],
            'orderBy' => ['type' => 'string', 'enum' => ['dateCreated desc', 'name asc', 'name desc']],
            'limit' => ElementQueryCriteria::SchemaProperties['limit'],
            'offset' => ElementQueryCriteria::SchemaProperties['offset'],
        ],
        'additionalProperties' => false,
    ];

    public function __construct(
        private ReleaseService $releases,
        private McpActor $actor,
        private ReleaseSerializer $serializer,
    ) {}

    /**
     * @param  array<string, mixed>  $criteria
     * @return array{count: int, limit: int, offset: int, nextOffset: int|null, releases: list<array<string, mixed>>}
     */
    #[McpTool(name: 'releases.list', description: 'Lists content releases, including published releases. Defaults to newest first; search matches names.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function list(#[Schema(definition: self::CriteriaSchema)] array $criteria = []): array
    {
        $this->validate(['criteria' => $criteria], [
            'criteria' => ['array:search,orderBy,limit,offset'],
            'criteria.search' => ['sometimes', 'string'],
            'criteria.orderBy' => ['sometimes', 'in:dateCreated desc,name asc,name desc'],
        ]);
        $limit = $criteria['limit'] ?? ElementQueryCriteria::DefaultLimit;
        $offset = $criteria['offset'] ?? 0;

        if (! is_int($limit) || ! is_int($offset)) {
            throw new ToolCallException('Release criteria.limit and criteria.offset must be integers.');
        }

        $limit = max(1, min($limit, ElementQueryCriteria::MaxLimit));
        $offset = max(0, $offset);
        $search = trim($criteria['search'] ?? '');
        $query = Release::query()->withCount('items')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"));

        if (str_starts_with($criteria['orderBy'] ?? '', 'name ')) {
            $query->orderBy('name', $criteria['orderBy'] === 'name desc' ? 'desc' : 'asc');
        }

        $releases = $query->orderByDesc('dateCreated')->orderByDesc('id')->offset($offset)->limit($limit + 1)->get();
        $hasMore = $releases->count() > $limit;
        $page = $releases->take($limit)->map(fn (Release $release): array => $this->serializer->serialize($release, includeItems: false))->values()->all();

        return ['count' => count($page), 'limit' => $limit, 'offset' => $offset, 'nextOffset' => $hasMore ? $offset + $limit : null, 'releases' => $page];
    }

    /** @return array{release: array<string, mixed>} */
    #[McpTool(name: 'releases.get', description: 'Reads a release and basic member metadata across sites. Content fields require drafts.get and content view permission.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function get(#[Schema(minimum: 1)] int $id): array
    {
        return ['release' => $this->serializer->serialize($this->release($id))];
    }

    /** @return array{release: array<string, mixed>} */
    #[McpTool(name: 'releases.create', description: 'Creates an empty content release. plannedAt is a planning date; scheduling is a separate operation.')]
    #[RequiresPermission('manageReleases')]
    public function create(string $name, ?string $description = null, #[Schema(format: 'date-time')] ?string $plannedAt = null): array
    {
        $changes = $this->metadata(['name' => $name, 'description' => $description, 'plannedAt' => $plannedAt]);

        return $this->result(fn (): Release => $this->releases->create($changes['name'], $changes['plannedAt'], $this->actor->user(), $changes['description']));
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array{release: array<string, mixed>}
     */
    #[McpTool(name: 'releases.update', description: 'Updates mutable release metadata. Omitted properties are preserved; null clears description or plannedAt. Scheduling is separate.')]
    #[RequiresPermission('manageReleases')]
    public function update(#[Schema(minimum: 1)] int $id, #[Schema(definition: self::MetadataSchema)] array $changes): array
    {
        $changes = $this->metadata($changes);

        return $this->result(fn (): Release => $this->releases->updateMetadata($this->release($id), $changes, $this->actor->user()));
    }

    /** @return array{deleted: true, id: int} */
    #[McpTool(name: 'releases.delete', description: 'Deletes a mutable release, preserving its unpublished drafts.', annotations: new ToolAnnotations(destructiveHint: true))]
    #[RequiresPermission('manageReleases')]
    public function delete(#[Schema(minimum: 1)] int $id): array
    {
        $this->run(fn () => $this->releases->delete($this->release($id), $this->actor->user()));

        return ['deleted' => true, 'id' => $id];
    }

    /** @return array{release: array<string, mixed>} */
    #[McpTool(name: 'releases.attach', description: 'Adds a saved, non-provisional root draft to a mutable release. Uses the draft record ID and checks content publication permission across sites and workflow approval.')]
    public function attach(#[Schema(minimum: 1)] int $id, #[Schema(minimum: 1)] int $draftId): array
    {
        return $this->result(function () use ($id, $draftId): Release {
            $release = $this->release($id);
            $draft = Draft::findElement($draftId) ?? throw new ToolCallException('Draft not found.');
            $this->releases->attach($release, $draft, $this->actor->user());

            return $release->refresh();
        });
    }

    /** @return array{release: array<string, mixed>} */
    #[McpTool(name: 'releases.detach', description: 'Removes a draft from a mutable release without deleting it. Scheduled releases must first be unscheduled.')]
    public function detach(#[Schema(minimum: 1)] int $id, #[Schema(minimum: 1)] int $draftId): array
    {
        return $this->result(function () use ($id, $draftId): Release {
            $release = $this->release($id);
            $this->releases->detach($release, $draftId, $this->actor->user());

            return $release->refresh();
        });
    }

    /** @return array<string, mixed> */
    #[McpTool(name: 'releases.check-readiness', description: 'Checks readiness and returns per-item errors and warnings without changing or publishing content. Requires manageReleases or publishReleases. Publishing still checks current state.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function checkReadiness(#[Schema(minimum: 1)] int $id): array
    {
        return $this->run(fn (): array => ['id' => $id, 'readiness' => $this->releases->checkReadiness($this->release($id), $this->actor->user())->toArray()]);
    }

    /** @return array{release: array<string, mixed>} */
    #[McpTool(name: 'releases.schedule', description: 'Schedules a ready release for a future ISO timestamp with Z or an explicit offset. publishReleases is independent of permission to edit individual content.')]
    #[RequiresPermission('publishReleases')]
    public function schedule(#[Schema(minimum: 1)] int $id, #[Schema(format: 'date-time')] string $publishAt): array
    {
        $this->validate(['publishAt' => $publishAt], ['publishAt' => ['required', ...$this->timestampRules()]]);
        $date = CarbonImmutable::parse($publishAt)->setTimezone(date_default_timezone_get());

        return $this->result(fn (): Release => $this->releases->schedule($this->release($id), $date, $this->actor->user()));
    }

    /** @return array{release: array<string, mixed>} */
    #[McpTool(name: 'releases.unschedule', description: 'Unschedules a release and makes it mutable while preserving its planning date.')]
    #[RequiresPermission('publishReleases')]
    public function unschedule(#[Schema(minimum: 1)] int $id): array
    {
        return $this->result(fn (): Release => $this->releases->unschedule($this->release($id), $this->actor->user()));
    }

    /** @return array{release: array<string, mixed>} */
    #[McpTool(name: 'releases.publish', description: 'Publishes a mutable release atomically after merging live changes and checking current workflow approval and validation. Scheduled releases must first be unscheduled. After an uncertain response, use releases.get before retrying.', annotations: new ToolAnnotations(destructiveHint: true))]
    #[RequiresPermission('publishReleases')]
    public function publish(#[Schema(minimum: 1)] int $id): array
    {
        return $this->result(fn (): Release => $this->releases->publish($this->release($id), $this->actor->user()));
    }

    private function release(int $id): Release
    {
        return Release::query()->find($id) ?? throw new ToolCallException('Release not found.');
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    private function run(Closure $callback): mixed
    {
        try {
            return $callback();
        } catch (HttpExceptionInterface|WorkflowException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        } catch (InvalidElementException $exception) {
            throw new ToolCallException(implode("\n", $exception->element->errors()->all()) ?: 'The release draft could not be published.', previous: $exception);
        }
    }

    /**
     * @param  Closure(): Release  $callback
     * @return array{release: array<string, mixed>}
     */
    private function result(Closure $callback): array
    {
        return $this->run(fn (): array => ['release' => $this->serializer->serialize($callback())]);
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array{name?: string, description?: string|null, plannedAt?: CarbonImmutable|null}
     */
    private function metadata(array $changes): array
    {
        if (isset($changes['name']) && is_string($changes['name'])) {
            $changes['name'] = trim($changes['name']);
        }

        $changes = $this->validate(['changes' => $changes], [
            'changes' => ['required', 'array:name,description,plannedAt'],
            'changes.name' => ['sometimes', 'required', 'string', 'max:255'],
            'changes.description' => ['sometimes', 'nullable', 'string'],
            'changes.plannedAt' => ['sometimes', 'nullable', ...$this->timestampRules()],
        ])['changes'];

        if (isset($changes['plannedAt'])) {
            $changes['plannedAt'] = CarbonImmutable::parse($changes['plannedAt'])->setTimezone(date_default_timezone_get());
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function validate(array $data, array $rules): array
    {
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ToolCallException($validator->errors()->first());
        }

        return $validator->validated();
    }

    /** @return list<string> */
    private function timestampRules(): array
    {
        return ['string', 'date', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/D'];
    }
}
