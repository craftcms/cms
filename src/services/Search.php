<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\base\MemoizableArray;
use craft\cache\ElementQueryTagDependency;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\events\IndexKeywordsEvent;
use craft\events\SearchEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Search as SearchHelper;
use craft\helpers\StringHelper;
use craft\models\Site;
use craft\search\SearchQuery;
use craft\search\SearchQueryTerm;
use craft\search\SearchQueryTermGroup;
use Throwable;
use yii\base\Component;
use yii\db\Expression;
use yii\db\Schema;

/**
 * Search service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getSearch()|`Craft::$app->search`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Search extends Component
{
    /**
     * @event IndexKeywordsEvent The event that is triggered before keywords are indexed for an element attribute or field.
     *
     * You may set [[\craft\events\CancelableEvent::$isValid]] to `false` to prevent the attribute/field’s keywords from being indexed.
     *
     * @since 4.2.0
     */
    public const EVENT_BEFORE_INDEX_KEYWORDS = 'beforeIndexKeywords';

    /**
     * @event SearchEvent The event that is triggered before a search is performed.
     */
    public const EVENT_BEFORE_SEARCH = 'beforeSearch';

    /**
     * @event SearchEvent The event that is triggered after a search is performed.
     *
     * Any modifications to [[SearchEvent::$scores]] will be respected.
     */
    public const EVENT_AFTER_SEARCH = 'afterSearch';

    /**
     * @event SearchEvent The event that is triggered before the results are scored.
     *
     * Any modifications to [[SearchEvent::$results]] will be respected when results are scored.
     *
     * Event handlers can set [[SearchEvent::$scores]] to override the resulting element scores returned by [[searchElements()]].
     *
     * @since 4.3.0
     */
    public const EVENT_BEFORE_SCORE_RESULTS = 'beforeScoreResults';

    /**
     * @var bool Whether fulltext searches should be used ever. (MySQL only.)
     * @since 3.4.10
     */
    public bool $useFullText = true;

    /**
     * @var int|null The minimum word length that keywords must be in order to use a full-text search (MySQL only).
     */
    public ?int $minFullTextWordLength = null;

    /**
     * @var SearchQueryTerm[]
     */
    private array $_terms;

    /**
     * @var SearchQueryTerm[][]
     */
    private array $_groups;

    /**
     * @var bool
     */
    private bool $_isMysql;

    /**
     * @var array|null
     * @see _isSupportedFullTextWord()
     */
    private ?array $_mysqlStopWords = null;

    /**
     * @var int Because the `keywords` column in the search index table is a
     * B-TREE index on Postgres, you can get an "index row size exceeds maximum
     * for index" error with a lot of data. This value is a hard limit to
     * truncate search index data for a single row in Postgres.
     */
    public int $maxPostgresKeywordLength = 2450;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->_isMysql = Craft::$app->getDb()->getIsMysql();

        if ($this->_isMysql && !isset($this->minFullTextWordLength)) {
            $this->minFullTextWordLength = 4;
        }
    }

    /**
     * Indexes the attributes of a given element defined by its element type.
     *
     * @param ElementInterface $element
     * @param string[]|null $fieldHandles The field handles that should be indexed,
     * or `null` if all fields should be indexed.
     * @return bool Whether the indexing was a success.
     */
    public function indexElementAttributes(ElementInterface $element, ?array $fieldHandles = null): bool
    {
        // Acquire a lock for this element/site ID
        $mutex = Craft::$app->getMutex();
        $lockKey = "searchindex:$element->id:$element->siteId";

        if (!$mutex->acquire($lockKey)) {
            // Not worth waiting around; for all we know the other process has newer search attributes anyway
            return true;
        }

        // Figure out which fields to update, and which to ignore
        /** @var FieldInterface[] $updateFields */
        $updateFields = [];
        /** @var string[] $ignoreFieldIds */
        $ignoreFieldIds = [];
        if ($element::hasContent() && ($fieldLayout = $element->getFieldLayout()) !== null) {
            if ($fieldHandles !== null) {
                $fieldHandles = array_flip($fieldHandles);
            }
            foreach ($fieldLayout->getCustomFields() as $field) {
                if ($field->searchable) {
                    // Are we updating this field's keywords?
                    if ($fieldHandles === null || isset($fieldHandles[$field->handle])) {
                        $updateFields[] = $field;
                    } else {
                        // Leave its existing keywords alone
                        $ignoreFieldIds[] = (string)$field->id;
                    }
                }
            }
        }

        // Clear the element’s current search keywords
        $deleteCondition = [
            'elementId' => $element->id,
            'siteId' => $element->siteId,
        ];
        if (!empty($ignoreFieldIds)) {
            $deleteCondition = ['and', $deleteCondition, ['not', ['fieldId' => $ignoreFieldIds]]];
        }
        Db::delete(Table::SEARCHINDEX, $deleteCondition);

        // Update the element attributes' keywords
        $searchableAttributes = array_flip($element::searchableAttributes());
        $searchableAttributes['slug'] = true;
        if ($element::hasTitles()) {
            $searchableAttributes['title'] = true;
        }
        foreach (array_keys($searchableAttributes) as $attribute) {
            $value = $element->getSearchKeywords($attribute);
            $this->_indexKeywords($element, $value, attribute: $attribute);
        }

        // Update the custom fields' keywords
        foreach ($updateFields as $field) {
            $fieldValue = $element->getFieldValue($field->handle);
            $keywords = $field->getSearchKeywords($fieldValue, $element);
            $this->_indexKeywords($element, $keywords, fieldId: $field->id);
        }

        // Release the lock
        $mutex->release($lockKey);

        return true;
    }

    /**
     * Searches for elements that match the given element query.
     *
     * @param ElementQuery $elementQuery The element query being executed
     * @return int[] The filtered list of element IDs.
     * @phpstan-return array<int,int>
     * @since 3.7.14
     */
    public function searchElements(ElementQuery $elementQuery): array
    {
        $searchQuery = $elementQuery->search;
        if (is_string($searchQuery)) {
            $searchQuery = new SearchQuery($searchQuery, Craft::$app->getConfig()->getGeneral()->defaultSearchTermOptions);
        } elseif (is_array($searchQuery)) {
            $options = array_merge($searchQuery);
            $searchQuery = ArrayHelper::remove($options, 'query');
            $options = array_merge(Craft::$app->getConfig()->getGeneral()->defaultSearchTermOptions, $options);
            $searchQuery = new SearchQuery($searchQuery, $options);
        }

        $elementQuery = (clone $elementQuery)
            ->search(null)
            ->offset(null)
            ->limit(null);

        // Fire a 'beforeSearch' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SEARCH)) {
            $this->trigger(self::EVENT_BEFORE_SEARCH, new SearchEvent([
                'elementQuery' => $elementQuery,
                'query' => $searchQuery,
                'siteId' => $elementQuery->siteId,
            ]));
        }

        // Get tokens for query
        $this->_terms = [];
        $this->_groups = [];

        // Set Terms and Groups based on tokens
        foreach ($searchQuery->getTokens() as $obj) {
            if ($obj instanceof SearchQueryTermGroup) {
                $this->_groups[] = $obj->terms;
            } else {
                $this->_terms[] = $obj;
            }
        }

        if ($elementQuery->customFields !== null) {
            $customFields = new MemoizableArray($elementQuery->customFields);
        } else {
            $customFields = null;
        }

        // Get where clause from tokens, bail out if no valid query is there
        $where = $this->_getWhereClause($elementQuery->siteId, $customFields);

        if (empty($where)) {
            return [];
        }

        $query = (new Query())
            ->from([Table::SEARCHINDEX])
            ->where(new Expression($where))
            ->andWhere([
                'elementId' => $elementQuery->select(['elements.id']),
            ])
            ->cache(true, new ElementQueryTagDependency($elementQuery));

        if ($elementQuery->siteId !== null) {
            $query->andWhere(['siteId' => $elementQuery->siteId]);
        }

        // Execute the sql
        $results = $query->all();

        // Score the results
        $scores = null;

        // Fire a 'beforeScoreResults' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SCORE_RESULTS)) {
            $event = new SearchEvent([
                'elementQuery' => $elementQuery,
                'query' => $searchQuery,
                'siteId' => $elementQuery->siteId,
                'results' => $results,
            ]);
            $this->trigger(self::EVENT_BEFORE_SCORE_RESULTS, $event);

            // Use whatever changes may have been made to the results (unless it was set to null for some reason)
            if ($event->results !== null) {
                $results = $event->results;
            }

            // If a handler set the scores, use that instead of figuring it out for ourselves.
            $scores = $event->scores;
        }

        if ($scores === null) {
            $scores = [];

            // Loop through results and calculate score per element
            foreach ($results as $row) {
                $elementId = $row['elementId'];
                $score = $this->_scoreRow($row, $elementQuery->siteId);

                if (!isset($scores[$elementId])) {
                    $scores[$elementId] = $score;
                } else {
                    $scores[$elementId] += $score;
                }
            }
        }

        arsort($scores);

        // Fire an 'afterSearch' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SEARCH)) {
            $event = new SearchEvent([
                'elementQuery' => $elementQuery,
                'query' => $searchQuery,
                'siteId' => $elementQuery->siteId,
                'results' => $results,
                'scores' => $scores,
            ]);
            $this->trigger(self::EVENT_AFTER_SEARCH, $event);

            // Return the scores from the event, in case any last minute changes were made to it
            if ($event->scores !== null) {
                return $event->scores;
            }
        }

        return $scores;
    }

    /**
     * Deletes any search indexes that belong to elements that don’t exist anymore.
     *
     * @since 3.2.10
     */
    public function deleteOrphanedIndexes(): void
    {
        $db = Craft::$app->getDb();
        $searchIndexTable = Table::SEARCHINDEX;
        $elementsTable = Table::ELEMENTS;

        if ($db->getIsMysql()) {
            $sql = <<<SQL
DELETE s.* FROM $searchIndexTable s
LEFT JOIN $elementsTable e ON e.id = s.elementId
WHERE e.id IS NULL
SQL;
        } else {
            $sql = <<<SQL
DELETE FROM $searchIndexTable s
WHERE NOT EXISTS (
    SELECT * FROM $elementsTable
    WHERE id = s."elementId"
)
SQL;
        }
        $db->createCommand($sql)->execute();
    }

    /**
     * Indexes keywords for a specific element attribute/field.
     *
     * @param ElementInterface $element
     * @param string $keywords
     * @param string|null $attribute
     * @param int|null $fieldId
     */
    private function _indexKeywords(ElementInterface $element, string $keywords, ?string $attribute = null, ?int $fieldId = null): void
    {
        if ($attribute !== null) {
            $attribute = strtolower($attribute);
        }

        // Clean 'em up
        $site = $element->getSite();
        $keywords = SearchHelper::normalizeKeywords($keywords, [], true, $site->language);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_INDEX_KEYWORDS)) {
            $event = new IndexKeywordsEvent([
                'element' => $element,
                'attribute' => $attribute,
                'fieldId' => $fieldId,
                'keywords' => $keywords,
            ]);
            $this->trigger(self::EVENT_BEFORE_INDEX_KEYWORDS, $event);

            if (!$event->isValid) {
                return;
            }

            $keywords = $event->keywords;
        }

        // Save 'em
        $columns = [
            'elementId' => $element->id,
            'attribute' => $attribute ?? 'field',
            'fieldId' => $fieldId ? (string)$fieldId : '0',
            'siteId' => $site->id,
        ];

        if ($keywords !== '') {
            // Add padding around keywords
            $keywords = ' ' . $keywords . ' ';
        }

        $db = Craft::$app->getDb();
        if ($isPgsql = $db->getIsPgsql()) {
            $maxSize = $this->maxPostgresKeywordLength;
        } else {
            $maxSize = Db::getTextualColumnStorageCapacity(Schema::TYPE_TEXT);
        }

        if ($maxSize !== null && $maxSize !== false) {
            $keywords = $this->_truncateSearchIndexKeywords($keywords, $maxSize);
        }

        $columns['keywords'] = $keywords;

        if ($isPgsql) {
            $columns['keywords_vector'] = $keywords;
        }

        // Insert/update the row in searchindex
        Db::insert(Table::SEARCHINDEX, $columns);
    }

    /**
     * Calculate score for a result.
     *
     * @param array $row A single result from the search query.
     * @param int|int[]|null $siteId
     * @return int The total score for this row.
     */
    private function _scoreRow(array $row, array|int|null $siteId = null): int
    {
        // Starting point
        $score = 0;

        // Loop through AND-terms and score each one against this row
        foreach ($this->_terms as $term) {
            $score += $this->_scoreTerm($term, $row, 1, $siteId);
        }

        // Loop through each group of OR-terms
        foreach ($this->_groups as $terms) {
            // OR-terms are weighted less depending on the amount of OR terms in the group
            $weight = 1 / count($terms);

            // Get the score for each term and add it to the total
            foreach ($terms as $term) {
                $score += $this->_scoreTerm($term, $row, $weight, $siteId);
            }
        }

        return (int)round($score);
    }

    /**
     * Calculate score for a row/term combination.
     *
     * @param SearchQueryTerm $term The SearchQueryTerm to score.
     * @param array $row The result row to score against.
     * @param float|int $weight Optional weight for this term.
     * @param int|int[]|null $siteId
     * @return float The total score for this term/row combination.
     */
    private function _scoreTerm(SearchQueryTerm $term, array $row, float|int $weight = 1, array|int|null $siteId = null): float
    {
        // Skip these terms: exact filtering is just that, no weighted search applies since all elements will
        // already apply for these filters.
        if ($term->exact || !($keywords = $this->_normalizeTerm($term->term, $siteId))) {
            return 0;
        }

        // Account for substrings
        if (!$term->subLeft) {
            $keywords = ' ' . $keywords;
        }

        if (!$term->subRight) {
            $keywords .= ' ';
        }

        // Get haystack and safe word count
        $haystack = $row['keywords'];
        $wordCount = count(array_filter(explode(' ', $haystack)));

        // Get number of matches
        $score = StringHelper::countSubstrings($haystack, $keywords);

        if ($score) {
            // Exact match
            if (trim($keywords) === trim($haystack)) {
                $mod = 100;
            } // Don't scale up for substring matches
            elseif ($term->subLeft || $term->subRight) {
                $mod = 10;
            } else {
                $mod = 50;
            }

            // If this is a title, 5X it
            if ($row['attribute'] === 'title') {
                $mod *= 5;
            }

            $score = ($score / $wordCount) * $mod * $weight;
        }

        return $score;
    }

    /**
     * Get the complete where clause for current tokens
     *
     * @param int|int[]|null $siteId The site ID(s) to search within
     * @param MemoizableArray<FieldInterface>|null $customFields
     * @return string|false
     */
    private function _getWhereClause(array|int|null $siteId, ?MemoizableArray $customFields): string|false
    {
        $where = [];

        // Add the regular terms to the WHERE clause
        if (!empty($this->_terms)) {
            $condition = $this->_processTokens($this->_terms, true, $siteId, $customFields);

            if ($condition === false) {
                return false;
            }

            $where[] = $condition;
        }

        // Add each group to the where clause
        foreach ($this->_groups as $group) {
            $condition = $this->_processTokens($group, false, $siteId, $customFields);

            if ($condition === false) {
                return false;
            }

            $where[] = $condition;
        }

        // And combine everything with AND
        return implode(' AND ', $where);
    }

    /**
     * Generates partial WHERE clause for search from given tokens
     *
     * @param array $tokens
     * @param bool $inclusive
     * @param int|int[]|null $siteId
     * @param MemoizableArray<FieldInterface>|null $customFields
     * @return string|false
     * @throws Throwable
     */
    private function _processTokens(array $tokens, bool $inclusive, array|int|null $siteId, ?MemoizableArray $customFields): string|false
    {
        $glue = $inclusive ? ' AND ' : ' OR ';
        $where = [];
        $words = [];

        $db = Craft::$app->getDb();

        foreach ($tokens as $obj) {
            // Get SQL and/or keywords
            [$sql, $keywords] = $this->_getSqlFromTerm($obj, $siteId, $customFields);

            if ($sql === false && $inclusive) {
                return false;
            }

            // If we have SQL, just add that
            if ($sql) {
                $where[] = $sql;
            } // No SQL but keywords, save them for later
            elseif ($keywords !== null && $keywords !== '') {
                if ($inclusive && $db->getIsMysql()) {
                    $keywords = '+' . $keywords;
                }

                $words[] = $keywords;
            }
        }

        // If we collected full-text words, combine them into one
        if (!empty($words)) {
            $where[] = $this->_sqlFullText($words, true, $glue);
        }

        // If we have valid where clauses now, stringify them
        if (!empty($where)) {
            // Implode WHERE clause to a string
            $where = implode($glue, $where);

            // And group together for non-inclusive queries
            if (!$inclusive) {
                $where = "($where)";
            }
        } else {
            // If the tokens didn't produce a valid where clause,
            // make sure we return false
            $where = false;
        }

        return $where;
    }

    /**
     * Generates a piece of WHERE clause for fallback (LIKE) search from search term
     * or returns keywords to use in a full text search clause
     *
     * @param SearchQueryTerm $term
     * @param int|int[]|null $siteId
     * @param MemoizableArray<FieldInterface>|null $customFields
     * @return array
     * @throws Throwable
     */
    private function _getSqlFromTerm(SearchQueryTerm $term, array|int|null $siteId, ?MemoizableArray $customFields): array
    {
        // Initiate return value
        $sql = null;
        $keywords = null;
        $isMysql = Craft::$app->getDb()->getIsMysql();

        // Check for other attributes
        if ($term->attribute !== null) {
            // Is attribute a valid fieldId?
            $fieldId = $this->_getFieldIdFromAttribute($term->attribute, $customFields);

            if (!empty($fieldId)) {
                $attr = 'fieldId';
                $val = $fieldId;
            } else {
                $attr = 'attribute';
                $val = strtolower($term->attribute);
            }

            // Use subselect for attributes
            if (is_array($val)) {
                $where = [];
                foreach ($val as $v) {
                    $where[] = $this->_sqlWhere($attr, '=', $v);
                }
                $subSelect = '(' . implode(' OR ', $where) . ')';
            } else {
                $subSelect = $this->_sqlWhere($attr, '=', $val);
            }
        } else {
            $subSelect = null;
        }

        // Sanitize term
        if ($term->term !== null) {
            $keywords = $this->_normalizeTerm($term->term, $siteId);

            // Make sure that it didn't result in an empty string (e.g. if they entered '&')
            // unless it's meant to search for *anything* (e.g. if they entered 'attribute:*').
            if ($keywords !== '' || $term->subLeft) {
                // If we're on PostgreSQL and this is a phrase or exact match, we have to special case it.
                if (!$isMysql && $term->phrase) {
                    $sql = $this->_sqlPhraseExactMatch($keywords, $term->exact);
                } else {
                    // Create fulltext clause from term
                    if ($this->_doFullTextSearch($keywords, $term)) {
                        if ($term->subRight) {
                            if ($isMysql) {
                                $keywords .= '*';
                            } else {
                                $keywords .= ':*';
                            }
                        }

                        // Add quotes for exact match
                        if ($isMysql && StringHelper::contains($keywords, ' ')) {
                            if (StringHelper::first($keywords, 1) === '*') {
                                $keywords = StringHelper::insert($keywords, '"', 1);
                            } else {
                                $keywords = '"' . $keywords;
                            }

                            if (StringHelper::last($keywords, 1) === '*') {
                                $keywords = StringHelper::insert($keywords, '"', StringHelper::length($keywords) - 1);
                            } else {
                                $keywords .= '"';
                            }
                        }

                        // Determine prefix for the full-text keyword
                        if ($term->exclude) {
                            $keywords = '-' . $keywords;
                        }

                        // Only create an SQL clause if there's a subselect. Otherwise, return the keywords.
                        if ($subSelect !== null) {
                            // If there is a subselect, create the full text SQL bit
                            $sql = $this->_sqlFullText($keywords);
                        }
                    } // Create LIKE clause from term
                    else {
                        if ($term->exact) {
                            // Create exact clause from term
                            $operator = $term->exclude ? 'NOT LIKE' : 'LIKE';
                            $keywords = ($term->subLeft ? '%' : ' ') . $keywords . ($term->subRight ? '%' : ' ');
                        } else {
                            // Create LIKE clause from term
                            $operator = $term->exclude ? 'NOT LIKE' : 'LIKE';
                            $keywords = ($term->subLeft ? '%' : '% ') . $keywords . ($term->subRight ? '%' : ' %');
                        }

                        // Generate the SQL
                        $sql = $this->_sqlWhere('keywords', $operator, $keywords);
                    }
                }
            }
        } else {
            // Support for attribute:* syntax to just check if something has *any* keyword value.
            if ($term->subLeft) {
                $sql = $this->_sqlWhere('keywords', '!=', '');
            }
        }

        // If we have a where clause in the subselect, add the keyword bit to it.
        if ($subSelect !== null && $sql !== null) {
            $sql = $this->_sqlSubSelect($subSelect . ' AND ' . $sql, $siteId);

            // We need to reset keywords even if the subselect ended up in no results.
            $keywords = null;
        }

        return [$sql, $keywords];
    }

    /**
     * Normalize term from tokens, keep a record for cache.
     *
     * @param string $term
     * @param int|int[]|null $siteId
     * @return string
     */
    private function _normalizeTerm(string $term, array|int|null $siteId = null): string
    {
        static $terms = [];

        if (!array_key_exists($term, $terms)) {
            if ($siteId && !is_array($siteId)) {
                $site = Craft::$app->getSites()->getSiteById($siteId);
            }
            $terms[$term] = SearchHelper::normalizeKeywords($term, [], true, $site->language ?? null);
        }

        return $terms[$term];
    }

    /**
     * Get the fieldId for given attribute or 0 for unmatched.
     *
     * @param string $attribute
     * @param MemoizableArray<FieldInterface>|null $customFields
     * @return int|int[]|null
     */
    private function _getFieldIdFromAttribute(string $attribute, ?MemoizableArray $customFields): array|int|null
    {
        if ($customFields !== null) {
            return ArrayHelper::getColumn($customFields->where('handle', $attribute)->all(), 'id');
        }

        $field = Craft::$app->getFields()->getFieldByHandle($attribute);
        return $field->id ?? null;
    }

    /**
     * Get SQL bit for simple WHERE clause
     *
     * @param string $key The attribute.
     * @param string $oper The operator.
     * @param string|int $val The value.
     * @return string
     */
    private function _sqlWhere(string $key, string $oper, string|int $val): string
    {
        $key = Craft::$app->getDb()->quoteColumnName($key);

        return sprintf("(%s %s '%s')", $key, $oper, $val);
    }

    /**
     * Get SQL necessary for a full text search.
     *
     * @param mixed $val String or Array of keywords
     * @param bool $bool Use In Boolean Mode or not
     * @param string $glue If multiple values are passed in as an array, the operator to combine them (AND or OR)
     * @return string
     * @throws Throwable
     */
    private function _sqlFullText(mixed $val, bool $bool = true, string $glue = ' AND '): string
    {
        $db = Craft::$app->getDb();

        if ($db->getIsMysql()) {
            return sprintf("MATCH(%s) AGAINST('%s'%s)", $db->quoteColumnName('keywords'), (is_array($val) ? implode(' ', $val) : $val), ($bool ? ' IN BOOLEAN MODE' : ''));
        }

        if ($glue === ' AND ') {
            $glue = ' & ';
        } else {
            $glue = ' | ';
        }

        if (is_array($val)) {
            foreach ($val as $key => $value) {
                if (StringHelper::contains($value, ' ')) {
                    $temp = explode(' ', $value);
                    $temp = implode(' & ', $temp);
                    $val[$key] = $temp;
                }
            }
        } else {
            // If where here, it's a single string with punctuation that's been stripped out (i.e. "multi-site").
            // We can assume "and".
            if (StringHelper::contains($val, ' ')) {
                $val = StringHelper::replace($val, ' ', ' & ');
            }
        }

        return sprintf("%s @@ '%s'::tsquery", $db->quoteColumnName('keywords_vector'), (is_array($val) ? implode($glue, $val) : $val));
    }

    /**
     * Get SQL bit for sub-selects.
     *
     * @param string $where
     * @param int|int[]|null $siteId
     * @return string|false
     */
    private function _sqlSubSelect(string $where, array|int|null $siteId): string|false
    {
        $query = (new Query())
            ->select(['elementId'])
            ->from([Table::SEARCHINDEX])
            ->where($where);

        if ($siteId !== null) {
            $query->andWhere(['siteId' => $siteId]);
        }

        $elementIds = $query->column();

        if (!empty($elementIds)) {
            return Craft::$app->getDb()->quoteColumnName('elementId') . ' IN (' . implode(', ', $elementIds) . ')';
        }

        return false;
    }

    /**
     * Whether or not to do a full text search or not.
     *
     * @param string $keywords
     * @param SearchQueryTerm $term
     * @return bool
     */
    private function _doFullTextSearch(string $keywords, SearchQueryTerm $term): bool
    {
        return
            $this->useFullText &&
            $keywords !== '' &&
            !$term->subLeft &&
            !$term->exact &&
            !$term->exclude &&
            $this->_isSupportedFullTextWord($keywords) &&
            // Workaround on MySQL until this gets fixed: https://bugs.mysql.com/bug.php?id=78485
            // Related issue: https://github.com/craftcms/cms/issues/3862
            !str_contains($keywords, ' ');
    }

    /**
     * This method will return PostgreSQL specific SQL necessary to find an exact phrase search.
     *
     * @param string $val The phrase or exact value to search for.
     * @param bool $exact Whether this should be an exact match or not.
     * @return string The SQL to perform the search.
     */
    private function _sqlPhraseExactMatch(string $val, bool $exact = false): string
    {
        $ftVal = explode(' ', $val);
        $ftVal = implode(' & ', $ftVal);
        $likeVal = !$exact ? '%' . $val . '%' : " $val ";

        $db = Craft::$app->getDb();

        return sprintf("%s @@ '%s'::tsquery AND %s LIKE '%s'", $db->quoteColumnName('keywords_vector'), $ftVal, $db->quoteColumnName('keywords'), $likeVal);
    }

    /**
     * @param string $keywords The string of space separated search keywords.
     * @param int $maxSize The maximum size the keywords string should be.
     * @return string The (possibly) truncated keyword string.
     */
    private function _truncateSearchIndexKeywords(string $keywords, int $maxSize): string
    {
        $cleanKeywordsLength = strlen($keywords);

        // Give ourselves a little wiggle room.
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $maxSize = (int)ceil($maxSize * 0.95);

        if ($cleanKeywordsLength > $maxSize) {
            // Time to truncate.
            $keywords = mb_strcut($keywords, 0, $maxSize);

            // Make sure we don't cut off a word in the middle.
            if ($keywords[mb_strlen($keywords) - 1] !== ' ') {
                $position = mb_strrpos($keywords, ' ');

                if ($position) {
                    $keywords = mb_substr($keywords, 0, $position + 1);
                }
            }
        }

        return $keywords;
    }

    /**
     * @param string $keyword
     * @return bool
     */
    private function _isSupportedFullTextWord(string $keyword): bool
    {
        if (!$this->_isMysql) {
            return true;
        }

        if ($this->minFullTextWordLength && strlen($keyword) < $this->minFullTextWordLength) {
            return false;
        }

        if (!isset($this->_mysqlStopWords)) {
            $this->_mysqlStopWords = [];
            // todo: make this list smaller when we start requiring MySQL 5.6+ and can start forcing the searchindex table to use InnoDB
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
                if (!$this->minFullTextWordLength || strlen($word) >= $this->minFullTextWordLength) {
                    $this->_mysqlStopWords[$word] = true;
                }
            }
        }

        return !isset($this->_mysqlStopWords[$keyword]);
    }
}
