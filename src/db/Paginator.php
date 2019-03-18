<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use yii\base\BaseObject;
use yii\db\Connection as YiiConnection;
use yii\db\Query;
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
 * $pageResults = $paginator->getResults();
 * ```
 * ```twig
 * {% set paginator = create('craft\\db\\Paginator', {
 *     pageSize: 10,
 *     currentPage: craft.app.request.pageNum,
 * }) %}
 *
 * {% set pageResults = paginator.getResults() %}
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
     * @var YiiConnection The DB connection to be used with the query.
     * If null, the `db` application component will be used.
     */
    public $db = 'db';

    /**
     * @var int The number of results to include for each page
     */
    public $pageSize = 100;

    /**
     * @var QueryInterface|Query The query being paginated
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
     * Constructor
     *
     * @param QueryInterface $query The query that should be paginated
     * @param array $config
     */
    public function __construct(QueryInterface $query, array $config = [])
    {
        $this->query = $query;
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, YiiConnection::class);
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
        $this->currentPage = max(1, $currentPage);
    }

    /**
     * Returns the results for the current page
     *
     * @return array
     */
    public function getPageResults(): array
    {
        $pageOffset = $this->getPageOffset();
        $pageLimit = max(0, min($this->pageSize, $this->getTotalResults() - $pageOffset));

        if (!$pageLimit) {
            return [];
        }

        $limit = $this->query->limit;
        $offset = $this->query->offset;

        $results = $this->query
            ->offset($pageOffset)
            ->limit($pageLimit)
            ->all($this->db);

        $this->query->limit = $limit;
        $this->query->offset = $offset;

        return $results;
    }

    /**
     * Returns the offset of the first result returned by [[getPageResults()]]
     *
     * @return int|float
     */
    public function getPageOffset()
    {
        return ($this->query->offset ?? 0) + ($this->pageSize * ($this->currentPage - 1));
    }
}
