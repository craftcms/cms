<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use craft\helpers\ArrayHelper;
use yii\base\BaseObject;
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
 *     pageSize: 10,
 *     currentPage: craft.app.request.pageNum,
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
     * @var YiiConnection|null The DB connection to be used with the query.
     * If null, the query will choose the connection to use.
     */
    public $db;

    /**
     * @var int The number of results to include for each page
     */
    public $pageSize = 100;

    /**
     * @var QueryInterface|YiiQuery The query being paginated
     */
    protected $query;

    /**
     * @var int The total query count
     */
    protected $totalResults;

    /**
     * @var int The total number of pages
     */
    protected $totalPages;

    /**
     * @var int The current page
     */
    protected $currentPage = 1;

    /**
     * @var array|null The current page’s results
     */
    private $_pageResults;

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
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if ($this->db !== null) {
            // Make sure that $db is a Connection instance
            $this->db = Instance::ensure($this->db, YiiConnection::class);
        }
    }

    /**
     * Returns the total number of query results
     *
     * @return int|float
     */
    public function getTotalResults()
    {
        if ($this->totalResults !== null) {
            return $this->totalResults;
        }

        $this->totalResults = $this->query->count('*', $this->db);

        // Factor in the offset and limit
        if ($this->query->offset) {
            $this->totalResults = max(0, $this->totalResults - $this->query->offset);
        }
        if ($this->query->limit && $this->totalResults > $this->query->limit) {
            $this->totalResults = $this->query->limit;
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
        if ($this->totalPages !== null) {
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
    public function setCurrentPage(int $currentPage)
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
        if ($this->_pageResults !== null) {
            return $this->_pageResults;
        }

        $pageOffset = ($this->query->offset ?? 0) + $this->getPageOffset();

        // Have we reached the last page, and would the default page size bleed past the total results?
        if ($this->pageSize * $this->currentPage > $this->getTotalResults()) {
            $pageLimit = max(0, $this->getTotalResults() - $this->getPageOffset());
        } else {
            $pageLimit = $this->pageSize;
        }

        if (!$pageLimit) {
            return [];
        }

        $limit = $this->query->limit;
        $offset = $this->query->offset;

        $this->_pageResults = $this->query
            ->offset($pageOffset)
            ->limit($pageLimit)
            ->all($this->db);

        $this->query->limit = $limit;
        $this->query->offset = $offset;

        return $this->_pageResults;
    }

    /**
     * Sets the results for the current page.
     *
     * @param array
     * @since 3.1.22
     */
    public function setPageResults(array $pageResults)
    {
        $this->_pageResults = $pageResults;
    }

    /**
     * Returns the offset of the first result returned by [[getPageResults()]]
     *
     * @return int|float
     */
    public function getPageOffset()
    {
        return $this->pageSize * ($this->currentPage - 1);
    }
}
