<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\db\Query;
use craft\db\Table;
use craft\errors\SiteNotFoundException;
use craft\events\SearchEvent;
use craft\helpers\Db;
use craft\helpers\Search as SearchHelper;
use craft\helpers\StringHelper;
use craft\models\Site;
use craft\search\SearchQuery;
use craft\search\SearchQueryTerm;
use craft\search\SearchQueryTermGroup;
use yii\base\Component;
use yii\db\Schema;

/**
 * Handles search operations.
 * An instance of the Search service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getSearch()|`Craft::$app->search`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Search extends Component
{
    /**
     * @event SearchEvent The event that is triggered before a search is performed.
     */
    const EVENT_BEFORE_SEARCH = 'beforeSearch';

    /**
     * @event SearchEvent The event that is triggered after a search is performed.
     */
    const EVENT_AFTER_SEARCH = 'afterSearch';

    /**
     * @var bool Whether fulltext searches should be used ever. (MySQL only.)
     * @since 3.4.10
     */
    public $useFullText = true;

    /**
     * @var int The minimum word length that keywords must be in order to use a full-text search.
     */
    public $minFullTextWordLength;

    /**
     * @var
     */
    private $_tokens;

    /**
     * @var
     */
    private $_terms;

    /**
     * @var
     */
    private $_groups;

    /**
     * @var int Because the `keywords` column in the search index table is a
     * B-TREE index on Postgres, you can get an "index row size exceeds maximum
     * for index" error with a lot of data. This value is a hard limit to
     * truncate search index data for a single row in Postgres.
     */
    public $maxPostgresKeywordLength = 2450;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->minFullTextWordLength === null) {
            if (Craft::$app->getDb()->getIsMysql()) {
                $this->minFullTextWordLength = 4;
            } else {
                $this->minFullTextWordLength = 1;
            }
        }
    }

    /**
     * Indexes the attributes of a given element defined by its element type.
     *
     * @param ElementInterface $element
     * @param string[]|null $fieldHandles The field handles that should be indexed,
     * or `null` if all fields should be indexed.
     * @return bool Whether the indexing was a success.
     * @throws SiteNotFoundException
     */
    public function indexElementAttributes(ElementInterface $element, array $fieldHandles = null): bool
    {
        // Acquire a lock for this element/site ID
        $mutex = Craft::$app->getMutex();
        /** @var Element $element */
        $lockKey = "searchindex:{$element->id}:{$element->siteId}";

        if (!$mutex->acquire($lockKey)) {
            // Not worth waiting around; for all we know the other process has newer search attributes anyway
            return true;
        }

        // Figure out which fields to update, and which to ignore
        /** @var Field[] $updateFields */
        $updateFields = [];
        /** @var string[] $ignoreFieldIds */
        $ignoreFieldIds = [];
        if ($element::hasContent() && ($fieldLayout = $element->getFieldLayout()) !== null) {
            if ($fieldHandles !== null) {
                $fieldHandles = array_flip($fieldHandles);
            }
            foreach ($fieldLayout->getFields() as $field) {
                /** @var Field $field */
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

        // Clear the element's current search keywords
        $deleteCondition = [
            'elementId' => $element->id,
            'siteId' => $element->siteId,
        ];
        if (!empty($ignoreFieldIds)) {
            $deleteCondition = ['and', $deleteCondition, ['not', ['fieldId' => $ignoreFieldIds]]];
        }
        Craft::$app->getDb()->createCommand()
            ->delete(Table::SEARCHINDEX, $deleteCondition)
            ->execute();

        // Update the element attributes' keywords
        $searchableAttributes = array_flip($element::searchableAttributes());
        $searchableAttributes['slug'] = true;
        if ($element::hasTitles()) {
            $searchableAttributes['title'] = true;
        }
        foreach (array_keys($searchableAttributes) as $attribute) {
            $value = $element->getSearchKeywords($attribute);
            $this->_indexElementKeywords($element->id, $attribute, '0', $element->siteId, $value);
        }

        // Update the custom fields' keywords
        foreach ($updateFields as $field) {
            $fieldValue = $element->getFieldValue($field->handle);
            $keywords = $field->getSearchKeywords($fieldValue, $element);
            $this->_indexElementKeywords($element->id, 'field', (string)$field->id, $element->siteId, $keywords);
        }

        // Release the lock
        $mutex->release($lockKey);

        return true;
    }

    /**
     * Indexes the field values for a given element and site.
     *
     * @param int $elementId The ID of the element getting indexed.
     * @param int $siteId The site ID of the content getting indexed.
     * @param array $fields The field values, indexed by field ID.
     * @return bool Whether the indexing was a success.
     * @throws SiteNotFoundException
     * @deprecated in 3.4.0. Use [[indexElementAttributes()]] instead.
     */
    public function indexElementFields(int $elementId, int $siteId, array $fields): bool
    {
        foreach ($fields as $fieldId => $value) {
            $this->_indexElementKeywords($elementId, 'field', (string)$fieldId, $siteId, $value);
        }

        return true;
    }

    /**
     * Filters a list of element IDs by a given search query.
     *
     * @param int[] $elementIds The list of element IDs to filter by the search query.
     * @param string|array|SearchQuery $query The search query (either a string or a SearchQuery instance)
     * @param bool $scoreResults Whether to order the results based on how closely they match the query.
     * @param int|int[]|null $siteId The site ID(s) to filter by.
     * @param bool $returnScores Whether the search scores should be included in the results. If true, results will be returned as `element ID => score`.
     * @return array The filtered list of element IDs.
     */
    public function filterElementIdsByQuery(array $elementIds, $query, bool $scoreResults = true, $siteId = null, bool $returnScores = false): array
    {
        if (is_string($query)) {
            $query = new SearchQuery($query, Craft::$app->getConfig()->getGeneral()->defaultSearchTermOptions);
        } else if (is_array($query)) {
            $options = $query;
            $query = $options['query'];
            unset($options['query']);
            $options = array_merge(Craft::$app->getConfig()->getGeneral()->defaultSearchTermOptions, $options);
            $query = new SearchQuery($query, $options);
        }

        // Fire a 'beforeSearch' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SEARCH)) {
            $this->trigger(self::EVENT_BEFORE_SEARCH, new SearchEvent([
                'elementIds' => $elementIds,
                'query' => $query,
                'siteId' => $siteId,
            ]));
        }

        // Get tokens for query
        $this->_tokens = $query->getTokens();
        $this->_terms = [];
        $this->_groups = [];

        // Set Terms and Groups based on tokens
        foreach ($this->_tokens as $obj) {
            if ($obj instanceof SearchQueryTermGroup) {
                $this->_groups[] = $obj->terms;
            } else {
                $this->_terms[] = $obj;
            }
        }

        // Get where clause from tokens, bail out if no valid query is there
        $where = $this->_getWhereClause($siteId);

        if ($where === false || empty($where)) {
            return [];
        }

        $db = Craft::$app->getDb();

        if ($siteId !== null) {
            if (is_array($siteId)) {
                $where .= sprintf(' AND %s IN (%s)',
                    $db->quoteColumnName('siteId'),
                    implode(',', $siteId)
                );
            } else {
                $where .= sprintf(' AND %s = %s', $db->quoteColumnName('siteId'), $db->quoteValue($siteId));
            }
        }

        // Begin creating SQL
        $sql = sprintf('SELECT * FROM %s WHERE %s', $db->quoteTableName(Table::SEARCHINDEX), $where);

        // Append elementIds to QSL
        if (!empty($elementIds)) {
            $sql .= sprintf(' AND %s IN (%s)',
                $db->quoteColumnName('elementId'),
                implode(',', $elementIds)
            );
        }

        // Execute the sql
        $results = $db->createCommand($sql)->queryAll();

        // Are we scoring the results?
        if ($scoreResults) {
            $scoresByElementId = [];

            // Loop through results and calculate score per element
            foreach ($results as $row) {
                $elementId = $row['elementId'];
                $score = $this->_scoreRow($row);

                if (!isset($scoresByElementId[$elementId])) {
                    $scoresByElementId[$elementId] = $score;
                } else {
                    $scoresByElementId[$elementId] += $score;
                }
            }

            // Sort found elementIds by score
            arsort($scoresByElementId);

            // Fire an 'afterSearch' event
            if ($this->hasEventHandlers(self::EVENT_AFTER_SEARCH)) {
                $this->trigger(self::EVENT_AFTER_SEARCH, new SearchEvent([
                    'elementIds' => array_keys($scoresByElementId),
                    'query' => $query,
                    'siteId' => $siteId,
                ]));
            }

            if ($returnScores) {
                return $scoresByElementId;
            }

            // Just return the ordered element IDs
            return array_keys($scoresByElementId);
        }

        // Don't apply score, just return the IDs
        $elementIds = [];

        foreach ($results as $row) {
            $elementIds[] = $row['elementId'];
        }

        $elementIds = array_unique($elementIds);

        // Fire an 'afterSearch' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SEARCH)) {
            $this->trigger(self::EVENT_AFTER_SEARCH, new SearchEvent([
                'elementIds' => $elementIds,
                'query' => $query,
                'siteId' => $siteId,
            ]));
        }

        return $elementIds;
    }

    /**
     * Deletes any search indexes that belong to elements that don’t exist anymore.
     *
     * @since 3.2.10
     */
    public function deleteOrphanedIndexes()
    {
        $db = Craft::$app->getDb();
        if ($db->getIsMysql()) {
            $sql = <<<SQL
DELETE s.* FROM {{%searchindex}} s
LEFT JOIN {{%elements}} e ON e.id = s.elementId
WHERE e.id IS NULL
SQL;
        } else {
            $sql = <<<SQL
DELETE FROM {{%searchindex}} s
WHERE NOT EXISTS (
    SELECT * FROM {{%elements}}
    WHERE id = s."elementId"
)
SQL;
        }
        $db->createCommand($sql)->execute();
    }

    /**
     * Indexes keywords for a specific element attribute/field.
     *
     * @param int $elementId
     * @param string $attribute
     * @param string $fieldId
     * @param int $siteId
     * @param string $dirtyKeywords
     * @throws SiteNotFoundException
     */
    private function _indexElementKeywords(int $elementId, string $attribute, string $fieldId, int $siteId, string $dirtyKeywords)
    {
        $attribute = strtolower($attribute);

        /** @var Site $site */
        $site = Craft::$app->getSites()->getSiteById($siteId);

        // Clean 'em up
        $cleanKeywords = SearchHelper::normalizeKeywords($dirtyKeywords, [], true, $site->language);

        // Save 'em
        $columns = [
            'elementId' => $elementId,
            'attribute' => $attribute,
            'fieldId' => $fieldId,
            'siteId' => $site->id,
        ];

        if ($cleanKeywords !== null && $cleanKeywords !== false && $cleanKeywords !== '') {
            // Add padding around keywords
            $cleanKeywords = ' ' . $cleanKeywords . ' ';
        }

        $db = Craft::$app->getDb();
        if ($isPgsql = $db->getIsPgsql()) {
            $maxSize = $this->maxPostgresKeywordLength;
        } else {
            $maxSize = Db::getTextualColumnStorageCapacity(Schema::TYPE_TEXT);
        }

        if ($maxSize !== null && $maxSize !== false) {
            $cleanKeywords = $this->_truncateSearchIndexKeywords($cleanKeywords, $maxSize);
        }

        $columns['keywords'] = $cleanKeywords;

        if ($isPgsql) {
            $columns['keywords_vector'] = $cleanKeywords;
        }

        // Insert/update the row in searchindex
        $db->createCommand()
            ->insert(Table::SEARCHINDEX, $columns, false)
            ->execute();
    }

    /**
     * Calculate score for a result.
     *
     * @param array $row A single result from the search query.
     * @return float The total score for this row.
     */
    private function _scoreRow(array $row): float
    {
        // Starting point
        $score = 0;

        // Loop through AND-terms and score each one against this row
        foreach ($this->_terms as $term) {
            $score += $this->_scoreTerm($term, $row);
        }

        // Loop through each group of OR-terms
        foreach ($this->_groups as $terms) {
            // OR-terms are weighted less depending on the amount of OR terms in the group
            $weight = 1 / count($terms);

            // Get the score for each term and add it to the total
            foreach ($terms as $term) {
                $score += $this->_scoreTerm($term, $row, $weight);
            }
        }

        return $score;
    }

    /**
     * Calculate score for a row/term combination.
     *
     * @param SearchQueryTerm $term The SearchQueryTerm to score.
     * @param array $row The result row to score against.
     * @param float|int $weight Optional weight for this term.
     * @return float The total score for this term/row combination.
     */
    private function _scoreTerm(SearchQueryTerm $term, array $row, $weight = 1): float
    {
        // Skip these terms: exact filtering is just that, no weighted search applies since all elements will
        // already apply for these filters.
        if ($term->exact || !($keywords = $this->_normalizeTerm($term->term))) {
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
            else if ($term->subLeft || $term->subRight) {
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
     * @return string|false
     */
    private function _getWhereClause($siteId)
    {
        $where = [];

        // Add the regular terms to the WHERE clause
        if (!empty($this->_terms)) {
            $condition = $this->_processTokens($this->_terms, true, $siteId);

            if ($condition === false) {
                return false;
            }

            $where[] = $condition;
        }

        // Add each group to the where clause
        foreach ($this->_groups as $group) {
            $condition = $this->_processTokens($group, false, $siteId);

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
     * @return string|false
     * @throws \Throwable
     */
    private function _processTokens(array $tokens, bool $inclusive, $siteId)
    {
        $glue = $inclusive ? ' AND ' : ' OR ';
        $where = [];
        $words = [];

        $db = Craft::$app->getDb();

        foreach ($tokens as $obj) {
            // Get SQL and/or keywords
            list($sql, $keywords) = $this->_getSqlFromTerm($obj, $siteId);

            if ($sql === false && $inclusive) {
                return false;
            }

            // If we have SQL, just add that
            if ($sql) {
                $where[] = $sql;
            } // No SQL but keywords, save them for later
            else if ($keywords !== null && $keywords !== '') {
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
                $where = "({$where})";
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
     * @return array
     * @throws \Throwable
     */
    private function _getSqlFromTerm(SearchQueryTerm $term, $siteId): array
    {
        // Initiate return value
        $sql = null;
        $keywords = null;
        $isMysql = Craft::$app->getDb()->getIsMysql();

        // Check for other attributes
        if ($term->attribute !== null) {
            // Is attribute a valid fieldId?
            $fieldId = $this->_getFieldIdFromAttribute($term->attribute);

            if ($fieldId) {
                $attr = 'fieldId';
                $val = $fieldId;
            } else {
                $attr = 'attribute';
                $val = $term->attribute;
            }

            // Use subselect for attributes
            $subSelect = $this->_sqlWhere($attr, '=', $val);
        } else {
            $subSelect = null;
        }

        // Sanitize term
        if ($term->term !== null) {
            $keywords = $this->_normalizeTerm($term->term);

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
     * @return string
     */
    private function _normalizeTerm(string $term): string
    {
        static $terms = [];

        if (!array_key_exists($term, $terms)) {
            $terms[$term] = SearchHelper::normalizeKeywords($term);
        }

        return $terms[$term];
    }

    /**
     * Get the fieldId for given attribute or 0 for unmatched.
     *
     * @param string $attribute
     * @return int
     */
    private function _getFieldIdFromAttribute(string $attribute): int
    {
        // Get field id from service
        /** @var Field $field */
        $field = Craft::$app->getFields()->getFieldByHandle($attribute);

        // Fallback to 0
        return $field ? $field->id : 0;
    }

    /**
     * Get SQL bit for simple WHERE clause
     *
     * @param string $key The attribute.
     * @param string $oper The operator.
     * @param string $val The value.
     * @return string
     */
    private function _sqlWhere(string $key, string $oper, string $val): string
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
     * @throws \Throwable
     */
    private function _sqlFullText($val, bool $bool = true, string $glue = ' AND '): string
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
                    $temp = explode(' ', $val[$key]);
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
    private function _sqlSubSelect(string $where, $siteId)
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
            strlen($keywords) >= $this->minFullTextWordLength &&
            // Workaround on MySQL until this gets fixed: https://bugs.mysql.com/bug.php?id=78485
            // Related issue: https://github.com/craftcms/cms/issues/3862
            strpos($keywords, ' ') === false;
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
        $likeVal = !$exact ? '%' . $val . '%' : $val;

        $db = Craft::$app->getDb();

        return sprintf("%s @@ '%s'::tsquery AND %s LIKE '%s'", $db->quoteColumnName('keywords_vector'), $ftVal, $db->quoteColumnName('keywords'), $likeVal);
    }

    /**
     * @param string $cleanKeywords The string of space separated search keywords.
     * @param int $maxSize The maximum size the keywords string should be.
     * @return string The (possibly) truncated keyword string.
     */
    private function _truncateSearchIndexKeywords(string $cleanKeywords, int $maxSize): string
    {
        $cleanKeywordsLength = strlen($cleanKeywords);

        // Give ourselves a little wiggle room.
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $maxSize = ceil($maxSize * 0.95);

        if ($cleanKeywordsLength > $maxSize) {
            // Time to truncate.
            $cleanKeywords = mb_strcut($cleanKeywords, 0, $maxSize);

            // Make sure we don't cut off a word in the middle.
            if ($cleanKeywords[mb_strlen($cleanKeywords) - 1] !== ' ') {
                $position = mb_strrpos($cleanKeywords, ' ');

                if ($position) {
                    $cleanKeywords = mb_substr($cleanKeywords, 0, $position + 1);
                }
            }
        }

        return $cleanKeywords;
    }
}
