<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use Carbon\CarbonImmutable;
use CraftCms\Cms\Activity\Activities;
use CraftCms\Cms\Activity\ActivityComments;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Http\Requests\ActivityCommentRequest;
use CraftCms\Cms\Mcp\ElementQueryCriteria;
use CraftCms\Cms\Mcp\ElementQueryFactory;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Mcp\Serializers\ActivityEventSerializer;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/** @since 6.0.0 */
readonly class Activity
{
    private const array CriteriaSchema = [
        'type' => 'object',
        'properties' => [
            'occurredFrom' => ['type' => 'string', 'format' => 'date-time', 'description' => 'Inclusive start timestamp with an explicit timezone offset.'],
            'occurredUntil' => ['type' => 'string', 'format' => 'date-time', 'description' => 'Inclusive end timestamp with an explicit timezone offset.'],
            'limit' => [...ElementQueryCriteria::SchemaProperties['limit'], 'description' => 'Maximum number of events to return.'],
            'offset' => [...ElementQueryCriteria::SchemaProperties['offset'], 'description' => 'Number of events to skip.'],
        ],
        'additionalProperties' => false,
    ];

    public function __construct(
        private Activities $activities,
        private ActivityComments $comments,
        private ElementQueryFactory $elementQueries,
        private McpActor $actor,
        private ActivityEventSerializer $events,
    ) {}

    /**
     * @param  array<string, mixed>  $criteria
     * @return array{count: int, limit: int, offset: int, events: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'activity.list',
        description: 'Lists an element’s activity timeline newest first, including recorded changes when available. Changes are not a complete content diff. Drafts share their canonical timeline. Comment edits and deletions are folded into their original event; date filters use the original occurrence time and do not discover later edits to older comments.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function list(
        #[Schema(description: 'Registered element type reference handle or class name.')]
        string $type,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        #[Schema(definition: self::CriteriaSchema)]
        array $criteria = [],
    ): array {
        $element = $this->element($type, $id, $uid, $siteId);
        $validator = Validator::make(['criteria' => $criteria], [
            'criteria' => ['array:occurredFrom,occurredUntil,limit,offset'],
            'criteria.occurredFrom' => $this->timestampRules(),
            'criteria.occurredUntil' => $this->timestampRules(),
        ]);

        if ($validator->fails()) {
            throw new ToolCallException($validator->errors()->first());
        }

        $limit = $criteria['limit'] ?? ElementQueryCriteria::DefaultLimit;
        $offset = $criteria['offset'] ?? 0;

        if (! is_int($limit) || ! is_int($offset)) {
            throw new ToolCallException('Activity criteria.limit and criteria.offset must be integers.');
        }

        $limit = max(1, min($limit, ElementQueryCriteria::MaxLimit));
        $offset = max(0, $offset);
        $from = isset($criteria['occurredFrom']) ? CarbonImmutable::parse($criteria['occurredFrom'])->setTimezone(date_default_timezone_get()) : null;
        $until = isset($criteria['occurredUntil']) ? CarbonImmutable::parse($criteria['occurredUntil'])->setTimezone(date_default_timezone_get()) : null;

        if ($from !== null && $until !== null && $from->greaterThan($until)) {
            throw new ToolCallException('occurredFrom must not be after occurredUntil.');
        }

        $query = $this->activities->query()
            ->subject(ActivitySubject::fromElement($element))
            ->whereNull('rootEventId')
            ->limit($limit)
            ->offset($offset);

        if ($element->siteId !== null) {
            $query->site($element->siteId);
        }

        if ($from !== null) {
            $query->occurredFrom($from->ceilSecond());
        }

        if ($until !== null) {
            $query->occurredUntil($until);
        }

        $events = $this->events->serialize($query->get(), $this->actor->user()->asElement());

        return ['count' => count($events), 'limit' => $limit, 'offset' => $offset, 'events' => $events];
    }

    /** @return array{event: array<string, mixed>} */
    #[McpTool(
        name: 'activity.comments.create',
        description: 'Adds an editorial Markdown comment as the authenticated user. Use only when requested, not after every mutation. Explicitly requested mentions use [@username](craft-user:123) and send notifications. Retrying creates another comment; inspect activity.list after an uncertain result before retrying.',
        annotations: new ToolAnnotations(destructiveHint: false, idempotentHint: false),
    )]
    public function createComment(
        #[Schema(description: 'Registered element type reference handle or class name.')]
        string $type,
        #[Schema(minLength: 1, maxLength: ActivityCommentRequest::MaxLength)]
        string $markdown,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
    ): array {
        $element = $this->element($type, $id, $uid, $siteId);

        if (blank($markdown) || mb_strlen($markdown) > ActivityCommentRequest::MaxLength) {
            throw new ToolCallException('Comments must contain between 1 and 10,000 characters.');
        }

        $site = $element->siteId !== null ? Site::get($element->siteId) : null;

        if ($element->siteId !== null && $site === null) {
            throw new ToolCallException('The activity site could not be found.');
        }

        $user = $this->actor->user()->asElement();
        $event = $this->comments->create($element->getCanonical(true), $user, $site, $markdown);

        return ['event' => $this->events->serialize(collect([$event]), $user)[0]];
    }

    private function element(string $type, ?int $id, ?string $uid, ?int $siteId): ElementInterface
    {
        if (count(Arr::whereNotNull([$id, $uid])) !== 1) {
            throw new ToolCallException('Provide exactly one of: id, uid.');
        }

        $query = $this->elementQueries->make($type)
            ->status(null)
            ->drafts(null)
            ->provisionalDrafts(null)
            ->revisions(null);

        Typecast::configure($query, Arr::whereNotNull(['id' => $id, 'uid' => $uid, 'siteId' => $siteId]));
        $element = $query->one();

        if (! $element || ! Gate::forUser($this->actor->user())->allows('view', $element->getCanonical(true))) {
            throw new ToolCallException('Element not found.');
        }

        return $element;
    }

    /** @return list<string> */
    private function timestampRules(): array
    {
        return ['sometimes', 'string', 'date', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/D'];
    }
}
