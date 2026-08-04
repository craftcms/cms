<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Arr;
use Illuminate\Pagination\LengthAwarePaginator;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\db\Connection as YiiConnection;
use yii\db\ExpressionInterface;
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
     * @var YiiConnection|array|class-string<YiiConnection>|null The DB connection to be used with the query.
     * If null, the query will choose the connection to use.
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

    private ?LengthAwarePaginator $paginator = null;

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
        $currentPage = Arr::pull($config, 'currentPage');

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
        return $this->resolvePaginator()->total();
    }

    /**
     * Returns the total number of pages
     *
     * @return int
     */
    public function getTotalPages(): int
    {
        return $this->resolvePaginator()->lastPage();
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
            $this->paginator = null;
        }
    }

    /**
     * Returns the results for the current page
     *
     * @return array
     */
    public function getPageResults(): array
    {
        return $this->resolvePaginator()->items();
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
        $this->paginator = null;
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

    public function getPaginator(): LengthAwarePaginator
    {
        return $this->resolvePaginator();
    }

    private function resolvePaginator(): LengthAwarePaginator
    {
        if ($this->paginator !== null) {
            return $this->paginator;
        }

        /** @var YiiQuery $query */
        $query = $this->query;

        $totalResults = $query->count('*', $this->db);

        if ($query->offset) {
            $totalResults = max(0, $totalResults - $query->offset);
        }

        if ($query->limit && !$query->limit instanceof ExpressionInterface && $totalResults > $query->limit) {
            $totalResults = $query->limit;
        }

        $currentPage = max(1, $this->currentPage);
        $totalPages = max((int) ceil($totalResults / $this->pageSize), 1);

        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
            $this->currentPage = $currentPage;
        }

        $pageResults = $this->_pageResults ?? $this->fetchPageResults($query, $totalResults);

        $this->totalResults = $totalResults;
        $this->totalPages = $totalPages;
        $this->_pageResults = $pageResults;
        $pageParam = Cms::config()->getPageTriggerParam();
        $this->paginator = new LengthAwarePaginator(
            items: $pageResults,
            total: $totalResults,
            perPage: $this->pageSize,
            currentPage: $currentPage,
            options: [
                'pageName' => $pageParam,
            ],
        );

        $this->paginator->appends(request()->except($pageParam));

        return $this->paginator;
    }

    /**
     * @return array<int, mixed>
     */
    private function fetchPageResults(YiiQuery $query, int $totalResults): array
    {
        $pageOffset = ($query->offset ?? 0) + $this->getPageOffset();
        $pageLimit = $this->pageSize;

        if ($this->pageSize * $this->currentPage > $totalResults) {
            $pageLimit = max(0, $totalResults - $this->getPageOffset());
        }

        if (!$pageLimit) {
            return [];
        }

        $limit = $query->limit;
        $offset = $query->offset;

        $pageResults = $query
            ->offset($pageOffset)
            ->limit($pageLimit)
            ->all($this->db);

        $query->limit = $limit;
        $query->offset = $offset;

        return $pageResults;
    }
}
