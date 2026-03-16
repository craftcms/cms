<?php

declare(strict_types=1);

namespace CraftCms\Cms\Search;

use craft\base\ElementInterface;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Search\Events\AfterSearch;
use CraftCms\Cms\Search\Events\BeforeIndexKeywords;
use CraftCms\Cms\Search\Events\BeforeScoreResults;
use CraftCms\Cms\Search\Events\BeforeSearch;
use CraftCms\Cms\Search\Jobs\UpdateSearchIndex;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\MemoizableArray;
use CraftCms\Cms\Support\Search as SearchHelper;
use CraftCms\Cms\Support\Str;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

#[Singleton]
class Search
{
    /**
     * @var bool Whether fulltext searches should be used ever. (MySQL only.)
     */
    public bool $useFullText = true;

    /**
     * @var int|null The minimum word length that keywords must be in order to use a full-text search (MySQL only).
     */
    public ?int $minFullTextWordLength = null;

    /**
     * @var SearchQueryTerm[]
     */
    private array $terms;

    /**
     * @var SearchQueryTerm[][]
     */
    private array $groups;

    private readonly bool $isMysql;

    private readonly bool $isPgsql;

    private ?array $mysqlStopWords = null;

    /**
     * @var int Because the `keywords` column in the search index table is a
     *          B-TREE index on Postgres, you can get an "index row size exceeds maximum
     *          for index" error with a lot of data. This value is a hard limit to
     *          truncate search index data for a single row in Postgres.
     */
    public int $maxPostgresKeywordLength = 2450;

    public function __construct()
    {
        $this->isMysql = DB::isMysql();
        $this->isPgsql = DB::isPgsql();

        if ($this->isMysql && ! isset($this->minFullTextWordLength)) {
            $this->minFullTextWordLength = 4;
        }
    }

    public function indexElementAttributes(ElementInterface $element, ?array $fieldHandles = null): bool
    {
        $mutex = Cache::lock("searchindex:$element->id:$element->siteId", 30);

        if (! $mutex->get()) {
            return true;
        }

        $customFields = $element->getFieldLayout()?->getCustomFields() ?? [];
        $updateFieldIds = [];
        $ignoreFieldIds = [];

        if (! empty($customFields)) {
            if ($fieldHandles !== null) {
                $fieldHandles = array_flip($fieldHandles);
            }
            foreach ($customFields as $field) {
                if ($field->searchable && ! isset($updateFieldIds[$field->id])) {
                    if ($fieldHandles === null || isset($fieldHandles[$field->handle])) {
                        $updateFieldIds[$field->id] = true;
                        unset($ignoreFieldIds[$field->id]);
                    } else {
                        $ignoreFieldIds[$field->id] = true;
                    }
                }
            }
        }

        DB::table(Table::SEARCHINDEX)
            ->where('elementId', $element->id)
            ->where('siteId', $element->siteId)
            ->unless(
                empty($ignoreFieldIds),
                fn (Builder $query) => $query->whereNotIn('fieldId', array_map(fn (int $fieldId) => (string) $fieldId, array_keys($ignoreFieldIds))),
            )
            ->delete();

        foreach ($this->searchableAttributes($element) as $attribute) {
            $value = $element->getSearchKeywords($attribute);
            $this->indexKeywords($element, $value, attribute: $attribute);
        }

        $keywords = [];
        foreach ($customFields as $field) {
            if (isset($updateFieldIds[$field->id])) {
                $fieldValue = $element->getFieldValue($field->handle);
                $keywords[$field->id][] = $field->getSearchKeywords($fieldValue, $element);
            }
        }
        foreach ($keywords as $fieldId => $instanceKeywords) {
            $this->indexKeywords($element, implode(' ', $instanceKeywords), fieldId: $fieldId);
        }

        $mutex->release();

        TagDependency::invalidate([
            sprintf('element-search-query:%s', $element::class),
        ]);

        return true;
    }

    /** @return string[] */
    private function searchableAttributes(ElementInterface $element): array
    {
        $searchableAttributes = array_flip($element::searchableAttributes());
        $searchableAttributes['slug'] = true;

        if ($element::hasTitles()) {
            $searchableAttributes['title'] = true;
        }

        return array_keys($searchableAttributes);
    }

    /** @param  string[]  $fieldHandles */
    public function queueIndexElement(ElementInterface $element, array $fieldHandles): void
    {
        try {
            $this->createOrUpdateIndexJob($element, $fieldHandles);
        } catch (QueryException) {
            return;
        }

        dispatch(new UpdateSearchIndex(
            elementType: $element::class,
            elementId: $element->id,
            siteId: $element->siteId,
            queued: true,
        ))->onQueue(Cms::config()->lowPriorityQueueName);
    }

    /**
     * @throws QueryException
     */
    private function createOrUpdateIndexJob(ElementInterface $element, array $fieldHandles): void
    {
        $jobId = $this->pendingIndexJobId($element->id, $element->siteId);

        if ($jobId) {
            if (empty($fieldHandles)) {
                return;
            }

            $mutex = Cache::lock("searchqueue:$jobId", 30);

            if ($mutex->get()) {
                try {
                    if ($this->isIndexJobPending($jobId)) {
                        foreach ($fieldHandles as $fieldHandle) {
                            DB::table(Table::SEARCHINDEXQUEUE_FIELDS)
                                ->upsert([
                                    'jobId' => $jobId,
                                    'fieldHandle' => $fieldHandle,
                                ], ['jobId', 'fieldHandle']);
                        }

                        return;
                    }
                } finally {
                    $mutex->release();
                }
            }
        }

        DB::transaction(function () use ($element, $fieldHandles) {
            $jobId = DB::table(Table::SEARCHINDEXQUEUE)
                ->insertGetId([
                    'elementId' => $element->id,
                    'siteId' => $element->siteId,
                ]);

            $fieldData = array_map(fn (string $fieldHandle) => [
                'jobId' => $jobId,
                'fieldHandle' => $fieldHandle,
            ], $fieldHandles);

            DB::table(Table::SEARCHINDEXQUEUE_FIELDS)
                ->insert($fieldData);
        });
    }

    /** @param  class-string<ElementInterface>|null  $elementType */
    public function indexElementIfQueued(int $elementId, int $siteId, ?string $elementType = null): void
    {
        $jobId = $this->pendingIndexJobId($elementId, $siteId);

        if (! $jobId) {
            return;
        }

        $mutex = Cache::lock("searchqueue:$jobId", 5);

        if (! $mutex->get()) {
            throw new RuntimeException("Unable to acquire a mutex lock for search queue job $jobId");
        }

        try {
            if (! DB::table(Table::SEARCHINDEXQUEUE)->where('id', $jobId)->update(['reserved' => true])) {
                return;
            }
        } finally {
            $mutex->release();
        }

        try {
            $fieldHandles = DB::table(Table::SEARCHINDEXQUEUE_FIELDS)
                ->where('jobId', $jobId)
                ->pluck('fieldHandle')
                ->all();

            $elementType ??= DB::table(Table::ELEMENTS)
                ->where('id', $elementId)
                ->value('type');

            if ($elementType && is_a($elementType, ElementInterface::class, true)) {
                $element = $elementType::find()
                    ->drafts(null)
                    ->provisionalDrafts(null)
                    ->id($elementId)
                    ->siteId($siteId)
                    ->status(null)
                    ->one();

                if ($element) {
                    $this->indexElementAttributes($element, $fieldHandles);
                }
            }

            DB::table(Table::SEARCHINDEXQUEUE)->delete($jobId);
        } catch (Throwable $e) {
            DB::table(Table::SEARCHINDEXQUEUE)
                ->where('id', $jobId)
                ->update(['reserved' => false]);

            throw $e;
        }
    }

    private function pendingIndexJobId(int $elementId, int $siteId): ?int
    {
        return DB::table(Table::SEARCHINDEXQUEUE)
            ->where([
                'elementId' => $elementId,
                'siteId' => $siteId,
                'reserved' => false,
            ])
            ->value('id');
    }

    private function isIndexJobPending(int $jobId): bool
    {
        /** @var ?object{reserved: bool} $job */
        $job = DB::table(Table::SEARCHINDEXQUEUE)
            ->select('reserved')
            ->find($jobId);

        return $job !== null && ! $job->reserved;
    }

    public function shouldCallSearchElements(ElementQueryInterface $elementQuery): bool
    {
        return false;
    }

    /** @return array<string,int> The element scores (descending) indexed by element ID and site ID (e.g. `'100-1'`). */
    public function searchElements(ElementQueryInterface $elementQuery): array
    {
        $searchQuery = $this->normalizeSearchQuery($elementQuery->search);

        $elementQuery = (clone $elementQuery)
            ->select('elements.id')
            ->search(null)
            ->offset(null)
            ->limit(null);

        event(new BeforeSearch($elementQuery, $searchQuery));

        $query = $this->createDbQuery($searchQuery, $elementQuery);

        if ($query === false) {
            return [];
        }

        if ($elementQuery instanceof ElementQuery) {
            $elementQuery->reorder();
            $elementQuery->select('elements.id as id');
            $elementQuery->getSubQuery()->offset = null;
            $elementQuery->getSubQuery()->limit = null;
            $ids = $elementQuery->pluck('id')->all();
        } else {
            $ids = $elementQuery;
        }

        $results = $query
            ->whereIn('elementId', $ids)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        $scores = $this->scoreResults($results, $searchQuery, $elementQuery);

        event($event = new AfterSearch($elementQuery, $searchQuery, $results, $scores));

        $scores = $event->scores ?? $scores;

        ksort($scores, SORT_NATURAL);
        arsort($scores);

        return $scores;
    }

    public function createDbQuery(string|array|SearchQuery $searchQuery, ElementQueryInterface $elementQuery): Builder|false
    {
        $searchQuery = $this->normalizeSearchQuery($searchQuery);

        $this->terms = [];
        $this->groups = [];

        foreach ($searchQuery->getTokens() as $obj) {
            if ($obj instanceof SearchQueryTermGroup) {
                $this->groups[] = $obj->terms;
            } else {
                $this->terms[] = $obj;
            }
        }

        if ($elementQuery->customFields !== null) {
            $customFields = new MemoizableArray($elementQuery->customFields);
        } else {
            $customFields = null;
        }

        $where = $this->getWhereClause($elementQuery->siteId, $customFields);

        if ($where === false) {
            return false;
        }

        $query = DB::table(Table::SEARCHINDEX)->whereRaw($where['sql'], $where['bindings']);

        if ($elementQuery->siteId !== null) {
            $query->whereIn('siteId', Arr::wrap($elementQuery->siteId));
        }

        return $query;
    }

    private function scoreResults(array $results, SearchQuery $searchQuery, ElementQueryInterface $elementQuery): array
    {
        event($event = new BeforeScoreResults($elementQuery, $searchQuery, $results));

        if ($event->scores !== null) {
            return $event->scores;
        }

        if ($event->results !== null) {
            $results = $event->results;
        }

        $scores = [];

        foreach ($results as $row) {
            $key = sprintf('%s-%s', $row['elementId'], $row['siteId']);

            if (! isset($scores[$key])) {
                $scores[$key] = 0;
            }

            $scores[$key] += $this->scoreRow($row);
        }

        return $scores;
    }

    public function normalizeSearchQuery(string|array|SearchQuery $searchQuery): SearchQuery
    {
        if ($searchQuery instanceof SearchQuery) {
            return $searchQuery;
        }

        if (is_string($searchQuery)) {
            return new SearchQuery($searchQuery, Cms::config()->defaultSearchTermOptions);
        }

        $options = array_merge($searchQuery);
        $searchQuery = Arr::pull($options, 'query');
        $options = array_merge(Cms::config()->defaultSearchTermOptions, $options);

        return new SearchQuery($searchQuery, $options);
    }

    public function deleteOrphanedIndexes(): void
    {
        DB::table(Table::SEARCHINDEX, 's')
            ->whereNotExists(
                DB::table(Table::ELEMENTS_SITES, 'es')
                    ->whereColumn('elementId', 's.elementId')
                    ->whereColumn('siteId', 's.siteId'),
            )
            ->delete();
    }

    public function deleteOrphanedIndexJobs(): void
    {
        DB::table(Table::SEARCHINDEXQUEUE, 'q')
            ->whereNotExists(
                DB::table(Table::ELEMENTS_SITES, 'elements')
                    ->whereColumn('elementId', 'q.elementId')
                    ->whereColumn('siteId', 'q.siteId'),
            )
            ->delete();
    }

    private function indexKeywords(ElementInterface $element, string $keywords, ?string $attribute = null, ?int $fieldId = null): void
    {
        if ($attribute !== null) {
            $attribute = strtolower($attribute);
        }

        $site = $element->getSite();
        $keywords = SearchHelper::normalizeKeywords($keywords, [], true, $site->getLanguage());

        $event = new BeforeIndexKeywords($element, $attribute, $fieldId, $keywords);
        event($event);

        if (! $event->isValid) {
            return;
        }

        $keywords = $event->keywords;

        $columns = [
            'elementId' => $element->id,
            'attribute' => $attribute ?? 'field',
            'fieldId' => $fieldId ? (string) $fieldId : '0',
            'siteId' => $site->id,
        ];

        if ($keywords !== '') {
            $keywords = ' '.$keywords.' ';
        }

        $isPgsql = DB::connection()->isPgsql();
        if ($isPgsql) {
            $maxSize = $this->maxPostgresKeywordLength;
        } else {
            $maxSize = 65535;
        }

        $keywords = $this->truncateSearchIndexKeywords($keywords, $maxSize);

        $columns['keywords'] = $keywords;

        if ($isPgsql) {
            $columns['keywords_vector'] = $keywords;
        }

        retry(3, function () use ($columns) {
            DB::table(Table::SEARCHINDEX)
                ->insert($columns);
        }, 1_000, fn (Throwable $e) => $e instanceof QueryException && str_contains($e->getMessage(), 'deadlock'));
    }

    private function scoreRow(array $row): int
    {
        $score = 0;

        foreach ($this->terms as $term) {
            $score += $this->scoreTerm($term, $row, 1);
        }

        foreach ($this->groups as $terms) {
            $weight = 1 / count($terms);

            foreach ($terms as $term) {
                $score += $this->scoreTerm($term, $row, $weight);
            }
        }

        return (int) round($score);
    }

    private function scoreTerm(SearchQueryTerm $term, array $row, float|int $weight = 1): float
    {
        if ($term->exact || ! ($keywords = $this->normalizeTerm($term->term, $row['siteId']))) {
            return 0;
        }

        if (! $term->subLeft) {
            $keywords = ' '.$keywords;
        }

        if (! $term->subRight) {
            $keywords .= ' ';
        }

        $haystack = $row['keywords'];
        $wordCount = count(array_filter(explode(' ', (string) $haystack)));

        $score = Str::substrCount($haystack, $keywords);

        if ($score) {
            if (trim($keywords) === trim((string) $haystack)) {
                $mod = 100;
            } elseif ($term->subLeft || $term->subRight) {
                $mod = 10;
            } else {
                $mod = 50;
            }

            if ($row['attribute'] === 'title') {
                $mod *= 100;
            }

            $score = ($score / $wordCount) * $mod * $weight;
        }

        return $score;
    }

    /**
     * @param  int|int[]|null  $siteId
     * @param  MemoizableArray<FieldInterface>|null  $customFields
     * @return array{sql: string, bindings: list<int|float|string|bool|null>}|false
     */
    private function getWhereClause(array|int|null $siteId, ?MemoizableArray $customFields): array|false
    {
        $where = [];

        if (! empty($this->terms)) {
            $condition = $this->processTokens($this->terms, true, $siteId, $customFields);

            if ($condition === false) {
                return false;
            }

            $where[] = $condition;
        }

        foreach ($this->groups as $group) {
            $condition = $this->processTokens($group, false, $siteId, $customFields);

            if ($condition === false) {
                return false;
            }

            $where[] = $condition;
        }

        $where = $this->combineSqlConditions($where, ' AND ');

        return $where ?? false;
    }

    /**
     * @param  MemoizableArray<FieldInterface>|null  $customFields
     * @return array{sql: string, bindings: list<int|float|string|bool|null>}|false
     */
    private function processTokens(array $tokens, bool $inclusive, array|int|null $siteId, ?MemoizableArray $customFields): array|false
    {
        $glue = $inclusive ? ' AND ' : ' OR ';
        $where = [];
        $words = [];

        foreach ($tokens as $obj) {
            [$sql, $keywords] = $this->getSqlFromTerm($obj, $siteId, $customFields);

            if ($sql === false && $inclusive) {
                return false;
            }

            if ($sql) {
                $where[] = $sql;
            } elseif ($keywords !== null && $keywords !== '') {
                if ($inclusive && $this->isMysql) {
                    $keywords = '+'.$keywords;
                }

                $words[] = $keywords;
            }
        }

        if (! empty($words)) {
            $where[] = $this->sqlFullText($words, true, $glue);
        }

        $where = $this->combineSqlConditions($where, $glue);

        if ($where === null) {
            return false;
        }

        if (! $inclusive) {
            $where['sql'] = "({$where['sql']})";
        }

        return $where;
    }

    /**
     * @param  MemoizableArray<FieldInterface>|null  $customFields
     * @return array{0: array{sql: string, bindings: list<int|float|string|bool|null>}|false|null, 1: string|null}
     */
    private function getSqlFromTerm(SearchQueryTerm $term, array|int|null $siteId, ?MemoizableArray $customFields): array
    {
        $sql = null;
        $keywords = null;

        if ($term->attribute !== null) {
            $fieldId = $this->getFieldIdFromAttribute($term->attribute, $customFields);

            if (! empty($fieldId)) {
                $attr = 'fieldId';
                $val = $fieldId;
            } else {
                $attr = 'attribute';
                $val = strtolower($term->attribute);
            }

            if (is_array($val)) {
                $where = array_map(fn (int $v) => $this->sqlWhere($attr, '=', $v), $val);
                $subSelect = $this->combineSqlConditions($where, ' OR ');

                if ($subSelect !== null) {
                    $subSelect['sql'] = "({$subSelect['sql']})";
                }
            } else {
                $subSelect = $this->sqlWhere($attr, '=', $val);
            }
        } else {
            $subSelect = null;
        }

        if ($term->term !== null) {
            $keywords = $this->normalizeTerm($term->term, $siteId);

            if ($keywords !== '' || $term->subLeft) {
                $pgsqlPhrase = $this->isPgsql && $term->phrase;
                if ($pgsqlPhrase && $term->exact) {
                    $sql = $this->sqlPhraseExactMatch($keywords);
                } elseif (! $pgsqlPhrase && $this->doFullTextSearch($keywords, $term)) {
                    if ($term->subRight) {
                        if ($this->isMysql) {
                            $keywords .= '*';
                        } else {
                            $keywords .= ':*';
                        }
                    }

                    if ($this->isMysql && str_contains($keywords, ' ')) {
                        if (Str::take($keywords, 1) === '*') {
                            $keywords = Str::insert($keywords, '"', 1);
                        } else {
                            $keywords = '"'.$keywords;
                        }

                        if (Str::take($keywords, -1) === '*') {
                            $keywords = Str::insert($keywords, '"', Str::length($keywords) - 1);
                        } else {
                            $keywords .= '"';
                        }
                    }

                    if ($term->exclude) {
                        $keywords = '-'.$keywords;
                    }

                    if ($subSelect !== null) {
                        $sql = $this->sqlFullText($keywords);
                    }
                } else {
                    if ($term->exact) {
                        $operator = $term->exclude ? 'NOT LIKE' : 'LIKE';
                        $keywords = ($term->subLeft ? '%' : ' ').$keywords.($term->subRight ? '%' : ' ');
                    } else {
                        $operator = $term->exclude ? 'NOT LIKE' : 'LIKE';
                        $keywords = ($term->subLeft ? '%' : '% ').$keywords.($term->subRight ? '%' : ' %');
                    }

                    $sql = $this->sqlWhere('keywords', $operator, $keywords);
                }
            }
        } elseif ($term->subLeft) {
            $sql = $this->sqlWhere('keywords', '!=', '');
        }

        if ($subSelect !== null && $sql !== null) {
            $subSelectCondition = $this->combineSqlConditions([$subSelect, $sql], ' AND ');

            if ($subSelectCondition === null) {
                return [false, null];
            }

            $sql = $this->sqlSubSelect($subSelectCondition, $siteId);

            $keywords = null;
        }

        return [$sql, $keywords];
    }

    /** @param  int|int[]|null  $siteId */
    private function normalizeTerm(string $term, array|int|null $siteId = null): string
    {
        static $terms = [];

        if (! array_key_exists($term, $terms)) {
            if ($siteId && ! is_array($siteId)) {
                $site = Sites::getSiteById($siteId);
            }
            $terms[$term] = SearchHelper::normalizeKeywords($term, [], true, isset($site) ? $site->getLanguage() : null);
        }

        return $terms[$term];
    }

    /**
     * @param  MemoizableArray<FieldInterface>|null  $customFields
     * @return int|int[]|null
     */
    private function getFieldIdFromAttribute(string $attribute, ?MemoizableArray $customFields): array|int|null
    {
        if ($customFields !== null) {
            return array_map(
                fn (FieldInterface $field) => $field->id,
                $customFields->where('handle', $attribute)->all(),
            );
        }

        $field = app(Fields::class)->getFieldByHandle($attribute);

        return $field->id ?? null;
    }

    /** @return array{sql: string, bindings: list<int|float|string|bool|null>} */
    private function sqlWhere(string $key, string $oper, string|int $val): array
    {
        $key = DB::connection()->getQueryGrammar()->wrap($key);

        return [
            'sql' => sprintf('(%s %s ?)', $key, $oper),
            'bindings' => [$val],
        ];
    }

    /** @return array{sql: string, bindings: list<int|float|string|bool|null>} */
    private function sqlFullText(mixed $val, bool $bool = true, string $glue = ' AND '): array
    {
        $grammar = DB::connection()->getQueryGrammar();

        if ($this->isMysql) {
            return [
                'sql' => sprintf('MATCH(%s) AGAINST(?%s)', $grammar->wrap('keywords'), ($bool ? ' IN BOOLEAN MODE' : '')),
                'bindings' => [is_array($val) ? implode(' ', $val) : $val],
            ];
        }

        if ($glue === ' AND ') {
            $glue = ' & ';
        } else {
            $glue = ' | ';
        }

        if (is_array($val)) {
            foreach ($val as $key => $value) {
                if (str_contains((string) $value, ' ')) {
                    $temp = explode(' ', (string) $value);
                    $temp = implode(' & ', $temp);
                    $val[$key] = $temp;
                }
            }
        } else {
            if (str_contains((string) $val, ' ')) {
                $val = str_replace(' ', ' & ', $val);
            }
        }

        return [
            'sql' => sprintf('%s @@ ?::tsquery', $grammar->wrap('keywords_vector')),
            'bindings' => [is_array($val) ? implode($glue, $val) : $val],
        ];
    }

    /**
     * @param  array{sql: string, bindings: list<int|float|string|bool|null>}  $where
     * @param  int|int[]|null  $siteId
     * @return array{sql: string, bindings: list<int|float|string|bool|null>}|false
     */
    private function sqlSubSelect(array $where, array|int|null $siteId): array|false
    {
        $elementIds = DB::table(Table::SEARCHINDEX)
            ->whereRaw($where['sql'], $where['bindings'])
            ->when(
                $siteId !== null,
                fn (Builder $query) => $query->whereIn('siteId', Arr::wrap($siteId)),
            )
            ->pluck('elementId');

        if ($elementIds->isNotEmpty()) {
            $ids = $elementIds
                ->map(fn (int|string $id) => (int) $id)
                ->all();
            $placeholders = implode(', ', array_fill(0, count($ids), '?'));

            return [
                'sql' => sprintf('%s IN (%s)', DB::connection()->getQueryGrammar()->wrap('elementId'), $placeholders),
                'bindings' => $ids,
            ];
        }

        return false;
    }

    private function doFullTextSearch(string $keywords, SearchQueryTerm $term): bool
    {
        return
            ($this->isMysql || $this->isPgsql) &&
            $this->useFullText &&
            $keywords !== '' &&
            ! $term->subLeft &&
            ! $term->exact &&
            ! $term->exclude &&
            $this->isSupportedFullTextWord($keywords) &&
            ! str_contains($keywords, ' ');
    }

    /** @return array{sql: string, bindings: list<int|float|string|bool|null>} */
    private function sqlPhraseExactMatch(string $val): array
    {
        $ftVal = implode(' & ', explode(' ', $val));
        $grammar = DB::connection()->getQueryGrammar();

        return [
            'sql' => sprintf('%s @@ ?::tsquery AND %s LIKE ?', $grammar->wrap('keywords_vector'), $grammar->wrap('keywords')),
            'bindings' => [$ftVal, " $val "],
        ];
    }

    /**
     * @param  array<int, array{sql: string, bindings: list<int|float|string|bool|null>}>  $conditions
     * @return array{sql: string, bindings: list<int|float|string|bool|null>}|null
     */
    private function combineSqlConditions(array $conditions, string $glue): ?array
    {
        if (empty($conditions)) {
            return null;
        }

        return [
            'sql' => implode($glue, array_column($conditions, 'sql')),
            'bindings' => array_merge(...array_column($conditions, 'bindings')),
        ];
    }

    private function truncateSearchIndexKeywords(string $keywords, int $maxSize): string
    {
        $cleanKeywordsLength = strlen($keywords);

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $maxSize = (int) ceil($maxSize * 0.95);

        if ($cleanKeywordsLength > $maxSize) {
            $keywords = mb_strcut($keywords, 0, $maxSize);

            if ($keywords[mb_strlen($keywords) - 1] !== ' ') {
                $position = mb_strrpos($keywords, ' ');

                if ($position) {
                    $keywords = mb_substr($keywords, 0, $position + 1);
                }
            }
        }

        return $keywords;
    }

    private function isSupportedFullTextWord(string $keyword): bool
    {
        if (! $this->isMysql) {
            return true;
        }

        if ($this->minFullTextWordLength && strlen($keyword) < $this->minFullTextWordLength) {
            return false;
        }

        if (! isset($this->mysqlStopWords)) {
            $this->mysqlStopWords = [];
            $stopWords = [
                'able', 'about', 'above', 'according', 'accordingly', 'across', 'actually', 'after', 'afterwards', 'again', 'against', 'all', 'allow',
                'allows', 'almost', 'alone', 'along', 'already', 'also', 'although', 'always', 'am', 'among', 'amongst', 'an', 'and', 'another',
                'any', 'anybody', 'anyhow', 'anyone', 'anything', 'anyway', 'anyways', 'anywhere', 'apart', 'appear', 'appreciate', 'appropriate',
                'are', 'around', 'as', 'aside', 'ask', 'asking', 'associated', 'at', 'available', 'away', 'awfully', 'be', 'became', 'because',
                'become', 'becomes', 'becoming', 'been', 'before', 'beforehand', 'behind', 'being', 'believe', 'below', 'beside', 'besides', 'best',
                'better', 'between', 'beyond', 'both', 'brief', 'but', 'by', 'came', 'can', 'cannot', 'cant', 'cause', 'causes', 'certain',
                'certainly', 'changes', 'clearly', 'co', 'com', 'come', 'comes', 'concerning', 'consequently', 'consider', 'considering', 'contain',
                'containing', 'contains', 'corresponding', 'could', 'course', 'currently', 'definitely', 'described', 'despite', 'did', 'different',
                'do', 'does', 'doing', 'done', 'down', 'downwards', 'during', 'each', 'edu', 'eg', 'eight', 'either', 'else', 'elsewhere', 'enough',
                'entirely', 'especially', 'et', 'etc', 'even', 'ever', 'every', 'everybody', 'everyone', 'everything', 'everywhere', 'ex', 'exactly',
                'example', 'except', 'far', 'few', 'fifth', 'first', 'five', 'followed', 'following', 'follows', 'for', 'former', 'formerly', 'forth',
                'four', 'from', 'further', 'furthermore', 'get', 'gets', 'getting', 'given', 'gives', 'go', 'goes', 'going', 'gone', 'got', 'gotten',
                'greetings', 'had', 'happens', 'hardly', 'has', 'have', 'having', 'he', 'hello', 'help', 'hence', 'her', 'here', 'hereafter',
                'hereby', 'herein', 'hereupon', 'hers', 'herself', 'hi', 'him', 'himself', 'his', 'hither', 'hopefully', 'how', 'howbeit', 'however',
                'ie', 'if', 'ignored', 'immediate', 'in', 'inasmuch', 'inc', 'indeed', 'indicate', 'indicated', 'indicates', 'inner', 'insofar',
                'instead', 'into', 'inward', 'is', 'it', 'its', 'itself', 'just', 'keep', 'keeps', 'kept', 'know', 'known', 'knows', 'last', 'lately',
                'later', 'latter', 'latterly', 'least', 'less', 'lest', 'let', 'like', 'liked', 'likely', 'little', 'look', 'looking', 'looks', 'ltd',
                'mainly', 'many', 'may', 'maybe', 'me', 'mean', 'meanwhile', 'merely', 'might', 'more', 'moreover', 'most', 'mostly', 'much', 'must',
                'my', 'myself', 'name', 'namely', 'nd', 'near', 'nearly', 'necessary', 'need', 'needs', 'neither', 'never', 'nevertheless', 'new',
                'next', 'nine', 'no', 'nobody', 'non', 'none', 'noone', 'nor', 'normally', 'not', 'nothing', 'novel', 'now', 'nowhere', 'obviously',
                'of', 'off', 'often', 'oh', 'ok', 'okay', 'old', 'on', 'once', 'one', 'ones', 'only', 'onto', 'or', 'other', 'others', 'otherwise',
                'ought', 'our', 'ours', 'ourselves', 'out', 'outside', 'over', 'overall', 'own', 'particular', 'particularly', 'per', 'perhaps',
                'placed', 'please', 'plus', 'possible', 'presumably', 'probably', 'provides', 'que', 'quite', 'qv', 'rather', 'rd', 're', 'really',
                'reasonably', 'regarding', 'regardless', 'regards', 'relatively', 'respectively', 'right', 'said', 'same', 'saw', 'say', 'saying',
                'says', 'second', 'secondly', 'see', 'seeing', 'seem', 'seemed', 'seeming', 'seems', 'seen', 'self', 'selves', 'sensible', 'sent',
                'serious', 'seriously', 'seven', 'several', 'shall', 'she', 'should', 'since', 'six', 'so', 'some', 'somebody', 'somehow', 'someone',
                'something', 'sometime', 'sometimes', 'somewhat', 'somewhere', 'soon', 'sorry', 'specified', 'specify', 'specifying', 'still', 'sub',
                'such', 'sup', 'sure', 'take', 'taken', 'tell', 'tends', 'th', 'than', 'thank', 'thanks', 'thanx', 'that', 'thats', 'the', 'their',
                'theirs', 'them', 'themselves', 'then', 'thence', 'there', 'thereafter', 'thereby', 'therefore', 'therein', 'theres', 'thereupon',
                'these', 'they', 'think', 'third', 'this', 'thorough', 'thoroughly', 'those', 'though', 'three', 'through', 'throughout', 'thru',
                'thus', 'to', 'together', 'too', 'took', 'toward', 'towards', 'tried', 'tries', 'truly', 'try', 'trying', 'twice', 'two', 'un',
                'under', 'unfortunately', 'unless', 'unlikely', 'until', 'unto', 'up', 'upon', 'us', 'use', 'used', 'useful', 'uses', 'using',
                'usually', 'value', 'various', 'very', 'via', 'viz', 'vs', 'want', 'wants', 'was', 'way', 'we', 'welcome', 'well', 'went', 'were',
                'what', 'whatever', 'when', 'whence', 'whenever', 'where', 'whereafter', 'whereas', 'whereby', 'wherein', 'whereupon', 'wherever',
                'whether', 'which', 'while', 'whither', 'who', 'whoever', 'whole', 'whom', 'whose', 'why', 'will', 'willing', 'wish', 'with',
                'within', 'without', 'wonder', 'would', 'yes', 'yet', 'you', 'your', 'yours', 'yourself', 'yourselves', 'zero',
            ];
            foreach ($stopWords as $word) {
                if (! $this->minFullTextWordLength || strlen($word) >= $this->minFullTextWordLength) {
                    $this->mysqlStopWords[$word] = true;
                }
            }
        }

        return ! isset($this->mysqlStopWords[$keyword]);
    }
}
