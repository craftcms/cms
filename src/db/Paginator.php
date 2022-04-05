<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use craft\helpers\ArrayHelper;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\db\Connection as YiiConnection;
use yii\db\Query as YiiQuery;
use yii\db\QueryInterface;
use yii\di\Instance;

/**
 * Query Paginator
 *
 * ---
 * ```php
 * use craft\db\Paginator;
 *
 * $paginator = new Paginator($query, [
 *     'pageSize' => 10,
 *     'currentPage' => \Craft::$app->request->pageNum,
 * ]);
 *
 * $pageResults = $paginator->getPageResults();
 * ```
 * ```twig
 * {% set paginator = create('craft\\db\\Paginator', [query, {
 *   pageSize: 10,
 *   currentPage: craft.app.request.pageNum,
 * }]) %}
 *
 * {% set pageResults = paginator.getPageResults() %}
 * ```
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.19
 * @property-read int|float $totalResults The total number of query results
 * @property-read int $totalPages The total number of pages
 * @property int $currentPage The current page
 */
class Paginator extends BaseObject
{
    /**
     * @var YiiConnection|array|string|null The DB connection to be used with the query.
     * If null, the query will choose the connection to use.
     * @phpstan-var YiiConnection|array{class:class-string<YiiConnection>}|class-string<YiiConnection>|null
     */
    public YiiConnection|array|string|null $db = null;

    /**
     * @var int The number of results to include for each page
     */
    public int $pageSize = 100;

    /**
     * @var QueryInterface The query being paginated
     */
    protected QueryInterface $query;

    /**
     * @var int The total query count
     */
    protected int $totalResults;

    /**
     * @var int The total number of pages
     */
    protected int $totalPages;

    /**
     * @var int The current page
     */
    protected int $currentPage = 1;

    /**
     * @var array|null The current page’s results
     */
    private ?array $_pageResults = null;

    /**
     * Constructor
     *
     * @param QueryInterface $query The query that should be paginated
     * @param array $config
     */
    public function __construct(QueryInterface $query, array $config = [])
    {
        $this->query = $query;

        // Set the current page after everything else
        $currentPage = ArrayHelper::remove($config, 'currentPage');

        parent::__construct($config);

        if ($currentPage !== null) {
            $this->setCurrentPage($currentPage);
        }
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        if (isset($this->db)) {
            // Make sure that $db is a Connection instance
            $this->db = Instance::ensure($this->db, YiiConnection::class);
        }
    }

    /**
     * Returns the total number of query results
     *
     * @return int|float
     */
    public function getTotalResults(): float|int
    {
        if (isset($this->totalResults)) {
            return $this->totalResults;
        }

        /** @var YiiQuery $query */
        $query = $this->query;

        $this->totalResults = $query->count('*', $this->db);

        // Factor in the offset and limit
        if ($query->offset) {
            $this->totalResults = max(0, $this->totalResults - $query->offset);
        }
        if ($query->limit && $this->totalResults > $query->limit) {
            $this->totalResults = $query->limit;
        }

        return $this->totalResults;
    }

    /**
     * Returns the total number of pages
     *
     * @return int
     */
    public function getTotalPages(): int
    {
        if (isset($this->totalPages)) {
            return $this->totalPages;
        }
        $totalResults = $this->getTotalResults();
        return $this->totalPages = $totalResults ? (int)ceil($totalResults / $this->pageSize) : 1;
    }

    /**
     * Returns the current page
     *
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Sets the current page
     *
     * @param int $currentPage
     */
    public function setCurrentPage(int $currentPage): void
    {
        $currentPage = max(1, $currentPage);
        $currentPage = min($this->getTotalPages(), $currentPage);

        if ($currentPage !== $this->currentPage) {
            $this->currentPage = $currentPage;
            $this->_pageResults = null;
        }
    }

    /**
     * Returns the results for the current page
     *
     * @return array
     */
    public function getPageResults(): array
    {
        if (isset($this->_pageResults)) {
            return $this->_pageResults;
        }

        /** @var YiiQuery $query */
        $query = $this->query;

        $pageOffset = ($query->offset ?? 0) + $this->getPageOffset();

        // Have we reached the last page, and would the default page size bleed past the total results?
        if ($this->pageSize * $this->currentPage > $this->getTotalResults()) {
            $pageLimit = max(0, $this->getTotalResults() - $this->getPageOffset());
        } else {
            $pageLimit = $this->pageSize;
        }

        if (!$pageLimit) {
            return [];
        }

        $limit = $query->limit;
        $offset = $query->offset;

        $this->_pageResults = $query
            ->offset($pageOffset)
            ->limit($pageLimit)
            ->all($this->db);

        $query->limit = $limit;
        $query->offset = $offset;

        return $this->_pageResults;
    }

    /**
     * Sets the results for the current page.
     *
     * @param array $pageResults
     * @since 3.1.22
     */
    public function setPageResults(array $pageResults): void
    {
        $this->_pageResults = $pageResults;
    }

    /**
     * Returns the offset of the first result returned by [[getPageResults()]]
     *
     * @return int|float
     */
    public function getPageOffset(): float|int
    {
        return $this->pageSize * ($this->currentPage - 1);
    }
}
