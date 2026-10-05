<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Public;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Data\EagerLoadPlan;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Mcp\PublicElementTypes;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Database\Query\Builder;
use Mcp\Exception\ToolCallException;

/**
 * @since 6.0.0
 */
readonly class ElementQuery
{
    public function __construct(
        private Access $access,
        private ElementCriteria $criteria,
        private ElementSerializer $serializer,
        private PublicElementTypes $elementTypes,
        private Elements $elements,
    ) {}

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed>
     */
    public function query(string $name, array $criteria): array
    {
        $type = $this->elementTypes->make($name);

        if (! $this->allows($type)) {
            throw new ToolCallException("Public element type [$type->name] is not enabled. Use craft-context-get for allowed queryTypes.");
        }

        $this->criteria->assertSupported($type, $criteria);
        $criteria = $this->normalize($type, $criteria);
        $elements = $this->read($type, $criteria);

        return [
            'type' => $type->name,
            'elementType' => $type->class,
            'count' => count($elements),
            'limit' => $criteria['limit'],
            'offset' => $criteria['offset'],
            'elements' => array_map($this->serializer->serialize(...), $elements),
        ];
    }

    public function allows(ElementType $type): bool
    {
        if ($this->access->handles('sites') === []) {
            return false;
        }

        return match (true) {
            $type->is(Entry::class) => $this->access->ids('sections') !== []
                || $this->access->ids('nestedEntryFields') !== [],
            $type->is(Asset::class) => $this->access->ids('volumes') !== [],
            $type->is(User::class) => $this->allowsElementType($type) || $this->access->ids('userGroups') !== [],
            default => $this->allowsElementType($type),
        };
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return list<ElementInterface>
     */
    private function read(ElementType $type, array $criteria): array
    {
        $query = $type->query();
        $limit = $criteria['limit'];
        $offset = $criteria['offset'];
        $siteIds = $criteria['siteId'];
        $orderBy = Arr::pull($criteria, 'orderBy', 'id asc');

        unset($criteria['limit'], $criteria['offset'], $criteria['siteId']);

        Typecast::configure($query, $criteria);
        $this->sort($type, $query, $orderBy);
        $query->siteId($siteIds)->limit($limit)->offset($offset);
        $this->constrainToPublicScope($type, $query);

        return collect($query->all())
            ->filter(static fn (mixed $element): bool => $element instanceof ElementInterface)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed>
     */
    private function normalize(ElementType $type, array $criteria): array
    {
        $criteria = $this->normalizeSites($criteria);
        $criteria['limit'] = $this->limit($criteria['limit'] ?? null);

        if (array_key_exists('offset', $criteria) && ! is_int($criteria['offset'])) {
            throw new ToolCallException('Public query criteria.offset must be an integer.');
        }

        $criteria['offset'] = max(0, $criteria['offset'] ?? 0);
        $criteria = $this->normalizeElementState($type, $criteria);

        if (array_key_exists('relatedTo', $criteria)) {
            $this->assertPublicRelations($criteria['relatedTo'], $criteria['siteId']);
        }

        if (array_key_exists('with', $criteria)) {
            $criteria['with'] = $this->eagerLoadingPlans($criteria['with']);
        }

        if ($type->is(Asset::class)) {
            $criteria['volumeId'] = $this->publicIds('volumeId', $criteria['volumeId'] ?? '*', 'volumes');
        }

        if ($type->is(User::class) && ! $this->allowsElementType($type)) {
            $criteria['groupId'] = $this->publicIds('groupId', $criteria['groupId'] ?? '*', 'userGroups');
        }

        return $criteria;
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed>
     */
    private function normalizeElementState(ElementType $type, array $criteria): array
    {
        $flags = $this->access->elementFlags();

        if (! $flags['inactive']) {
            $criteria['archived'] = false;
            $criteria['status'] = $type->publicStatus();
        }

        if (! $flags['drafts']) {
            $criteria['drafts'] = false;
            $criteria['provisionalDrafts'] = false;
            $criteria['withProvisionalDrafts'] = false;
        }

        if (! $flags['revisions']) {
            $criteria['revisions'] = false;
        }

        return $criteria;
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed>
     */
    private function normalizeSites(array $criteria): array
    {
        $handles = $this->access->handles('sites');
        $allowedIds = $this->access->ids('sites');

        if ($handles === []) {
            throw new ToolCallException('Public MCP requires at least one enabled site.');
        }

        $siteIds = $allowedIds;

        if (array_key_exists('site', $criteria)) {
            $requestedHandles = $this->intersect('site', $criteria['site'], $handles);

            if ($requestedHandles === []) {
                throw new ToolCallException('Public query criteria.site must include at least one enabled public site.');
            }

            $siteIds = collect($requestedHandles)
                ->map(fn (string $handle): int => $allowedIds[array_search($handle, $handles, true)])
                ->all();
        }

        if (array_key_exists('siteId', $criteria)) {
            $siteIds = array_values(array_intersect(
                $siteIds,
                $this->intersect('siteId', $criteria['siteId'], $allowedIds),
            ));

            if ($siteIds === []) {
                throw new ToolCallException('Public query site criteria must include at least one matching enabled public site.');
            }
        }

        unset($criteria['site']);
        $criteria['siteId'] = $siteIds;

        return $criteria;
    }

    private function constrainToPublicScope(ElementType $type, ElementQueryInterface $query): void
    {
        if (! $this->allows($type)) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->whereIn('elements_sites.siteId', $this->access->ids('sites'));
        Typecast::configure($query, $this->normalizeElementState($type, []));

        if ($type->is(Asset::class)) {
            $query->whereIn('assets.volumeId', $this->access->ids('volumes'));

            return;
        }

        if ($type->is(User::class) && ! $this->allowsElementType($type)) {
            UserQuery::applyGroupId($query, $this->access->ids('userGroups'));

            return;
        }

        if (! $type->is(Entry::class)) {
            return;
        }

        $sectionIds = $this->access->ids('sections');
        $fieldIds = $this->access->ids('nestedEntryFields');

        $query->where(static function (Builder $query) use ($sectionIds, $fieldIds): void {
            if ($sectionIds !== []) {
                $query->orWhereIn('entries.sectionId', $sectionIds);
            }

            if ($fieldIds !== []) {
                $query->orWhereIn('entries.fieldId', $fieldIds);
            }
        });
    }

    private function sort(ElementType $type, ElementQueryInterface $query, mixed $orderBy): void
    {
        if (! is_string($orderBy) || trim($orderBy) === '') {
            throw new ToolCallException('Public query criteria.orderBy must be a nonempty string of public sort fields.');
        }

        $fields = $this->criteria->sortFields($type);
        $query->reorder();
        $hasId = false;

        foreach (explode(',', $orderBy) as $sort) {
            if (! preg_match('/^\s*([a-zA-Z][a-zA-Z0-9]*)\s*(?:\s+(asc|desc))?\s*$/i', $sort, $matches)
                || ! isset($fields[$matches[1]])) {
                throw new ToolCallException('Unsupported public sort field. Use craft-context-get for allowed orderBy fields.');
            }

            $query->orderBy($fields[$matches[1]], strtolower($matches[2] ?? 'asc'));
            $hasId = $hasId || $matches[1] === 'id';
        }

        if (! $hasId) {
            $query->orderBy('elements.id');
        }
    }

    /** @param list<int> $siteIds */
    private function assertPublicRelations(mixed $ids, array $siteIds): void
    {
        if (! is_array($ids) || ! array_is_list($ids) || $ids === [] || count($ids) > Access::MaxLimit
            || array_any($ids, static fn (mixed $id): bool => ! is_int($id) || $id < 1)) {
            throw new ToolCallException('Public query criteria.relatedTo must be a nonempty list of at most 100 positive element IDs.');
        }

        $remaining = array_unique($ids);

        foreach ($this->elementTypes->all() as $name => $class) {
            $type = new ElementType($name, $class);

            if (! $this->allows($type)) {
                continue;
            }

            $query = $type->query()
                ->id($remaining)
                ->siteId($siteIds)
                ->status(null)
                ->drafts(null)
                ->revisions(null)
                ->unique()
                ->limit(Access::MaxLimit);
            $this->constrainToPublicScope($type, $query);
            $publicIds = $query->ids();

            if ($this->access->elementFlags()['inactive']) {
                array_push($publicIds, ...(clone $query)->archived(true)->ids());
            }

            $remaining = array_diff($remaining, $publicIds);

            if ($remaining === []) {
                return;
            }
        }

        throw new ToolCallException('Every relatedTo element must be publicly queryable in the selected sites.');
    }

    /** @return list<EagerLoadPlan> */
    private function eagerLoadingPlans(mixed $paths): array
    {
        if (! is_array($paths) || ! array_is_list($paths) || count($paths) > 5
            || array_any($paths, static fn (mixed $path): bool => ! is_string($path)
                || ! preg_match('/^[a-zA-Z][a-zA-Z0-9_:]*(?:\.[a-zA-Z][a-zA-Z0-9_:]*){0,2}$/', $path))) {
            throw new ToolCallException('Public query criteria.with must contain at most five eager-loading paths, at most three levels deep.');
        }

        $plans = $this->elements->createEagerLoadingPlans(array_unique($paths));
        $this->constrainEagerLoadingPlans($plans);

        return $plans;
    }

    /** @param list<EagerLoadPlan> $plans */
    private function constrainEagerLoadingPlans(array $plans): void
    {
        foreach ($plans as $plan) {
            $plan->siteIds = $this->access->ids('sites');
            $plan->configureQuery = function (ElementQueryInterface $query): void {
                $type = $this->elementTypes->find($this->elementTypes->name($query->elementType));

                if ($type === null || $type->class !== $query->elementType) {
                    $query->whereRaw('0 = 1');

                    return;
                }

                $this->constrainToPublicScope($type, $query);
                $query->limit(Access::MaxLimit);
            };
            $this->constrainEagerLoadingPlans($plan->nested);
        }
    }

    private function allowsElementType(ElementType $type): bool
    {
        return in_array($type->name, $this->access->elementTypes(), true);
    }

    /** @return list<int|string> */
    private function publicIds(string $key, mixed $requested, string $scope): array
    {
        $ids = $this->intersect($key, $requested, $this->access->ids($scope));

        if ($ids === []) {
            throw new ToolCallException("Public query criteria.$key must include at least one enabled public scope.");
        }

        return $ids;
    }

    /**
     * @param  list<int|string>  $allowed
     * @return list<int|string>
     */
    private function intersect(string $key, mixed $requested, array $allowed): array
    {
        $requested = Arr::wrap($requested);

        if (in_array('*', $requested, true)) {
            return $allowed;
        }

        foreach ($requested as $value) {
            if (! is_int($value) && ! is_string($value)) {
                throw new ToolCallException("Public query criteria.$key must be a string, integer, or array of strings/integers.");
            }
        }

        return array_values(array_intersect($requested, $allowed));
    }

    private function limit(mixed $limit): int
    {
        if ($limit === null) {
            return Access::DefaultLimit;
        }

        if (! is_int($limit)) {
            throw new ToolCallException('Public query criteria.limit must be an integer.');
        }

        return max(1, min($limit, Access::MaxLimit));
    }
}
