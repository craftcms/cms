<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;
use craft\db\Paginator;
use craft\helpers\UrlHelper;
use yii\base\BaseObject;

/**
 * Paginate variable class.
 *
 * @property string $basePath
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Paginate extends BaseObject
{
    // Static
    // =========================================================================

    /**
     * Creates a new instance based on a Paginator object
     *
     * @param Paginator $paginator
     * @return static
     */
    public static function create(Paginator $paginator): self
    {
        $pageResults = $paginator->getPageResults();
        $pageOffset = $paginator->getPageOffset();

        return new static([
            'first' => $pageOffset + 1,
            'last' => $pageOffset + count($pageResults),
            'total' => $paginator->getTotalResults(),
            'currentPage' => $paginator->getCurrentPage(),
            'totalPages' => $paginator->getTotalPages(),
        ]);
    }

    // Properties
    // =========================================================================

    /**
     * @var
     */
    public $first;

    /**
     * @var
     */
    public $last;

    /**
     * @var
     */
    public $total = 0;

    /**
     * @var
     */
    public $currentPage;

    /**
     * @var
     */
    public $totalPages = 0;

    /**
     * @var string Base path
     * @see getBasePath()
     * @see setBasePath()
     */
    private $_basePath;

    // Public Methods
    // =========================================================================

    /**
     * Returns the base path that should be used for pagination URLs.
     *
     * @return string
     */
    public function getBasePath(): string
    {
        if ($this->_basePath !== null) {
            return $this->_basePath;
        }
        return $this->_basePath = Craft::$app->getRequest()->getPathInfo();
    }

    /**
     * Sets the base path that should be used for pagination URLs.
     *
     * @param string $basePath
     */
    public function setBasePath(string $basePath)
    {
        $this->_basePath = $basePath;
    }

    /**
     * Returns the URL to a specific page
     *
     * @param int $page
     * @return string|null
     */
    public function getPageUrl(int $page)
    {
        if ($page < 1 || $page > $this->totalPages) {
            return null;
        }

        $pageTrigger = Craft::$app->getConfig()->getGeneral()->getPageTrigger();
        $useQueryParam = strpos($pageTrigger, '?') === 0;

        $path = $this->getBasePath();

        // If not using a query param, append the page to the path
        if (!$useQueryParam && $page != 1) {
            if ($path) {
                $path .= '/';
            }

            $path .= $pageTrigger . $page;
        }

        // Build the URL with the same query string as the current request
        $url = UrlHelper::url($path, Craft::$app->getRequest()->getQueryStringWithoutPath());

        // If using a query param, append or remove it
        if ($useQueryParam) {
            $param = trim($pageTrigger, '?=');
            if ($page != 1) {
                $url = UrlHelper::urlWithParams($url, [$param => $page]);
            } else {
                $url = UrlHelper::removeParam($url, $param);
            }
        }

        return $url;
    }

    /**
     * Returns the URL to the first page.
     *
     * @return string|null
     */
    public function getFirstUrl()
    {
        return $this->getPageUrl(1);
    }

    /**
     * Returns the URL to the next page.
     *
     * @return string|null
     */
    public function getLastUrl()
    {
        return $this->getPageUrl($this->totalPages);
    }

    /**
     * Returns the URL to the previous page.
     *
     * @return string|null
     */
    public function getPrevUrl()
    {
        return $this->getPageUrl($this->currentPage - 1);
    }

    /**
     * Returns the URL to the next page.
     *
     * @return string|null
     */
    public function getNextUrl()
    {
        return $this->getPageUrl($this->currentPage + 1);
    }

    /**
     * Returns previous page URLs up to a certain distance from the current page.
     *
     * @param int|null $dist
     * @return array
     */
    public function getPrevUrls(int $dist = null): array
    {
        if ($dist !== null) {
            $start = $this->currentPage - $dist;
        } else {
            $start = 1;
        }

        return $this->getRangeUrls($start, $this->currentPage - 1);
    }

    /**
     * Returns next page URLs up to a certain distance from the current page.
     *
     * @param int|null $dist
     * @return array
     */
    public function getNextUrls(int $dist = null): array
    {
        if ($dist !== null) {
            $end = $this->currentPage + $dist;
        } else {
            $end = $this->totalPages;
        }

        return $this->getRangeUrls($this->currentPage + 1, $end);
    }

    /**
     * Returns a range of page URLs.
     *
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getRangeUrls(int $start, int $end): array
    {
        if ($start < 1) {
            $start = 1;
        }

        if ($end > $this->totalPages) {
            $end = $this->totalPages;
        }

        $urls = [];

        for ($page = $start; $page <= $end; $page++) {
            $urls[$page] = $this->getPageUrl($page);
        }

        return $urls;
    }
    
    /**
     * 
     * Method to get Google like page URLs
     * 
     * @param int $plusMinus Amount of links above and below current page (if possible)
     * @param bool $includeFirstLastPage If true, the first and the last page are also added to the returned Array of links
     * @return array
     */
    public function getDynamicRangeUrls($plusMinus = 4, $includeFirstLastPage = false)
    {
        $total = $this->totalPages;
        // not a single result, return empty array
        if ($total == 0) {
            return [];
        }
        $current = $this->currentPage;

        // Calculate amount of links to return
        $amount = 2 * $plusMinus + 1;
        $amount = ($amount < $total) ? $total : $amount;

        // Where to start / end with the Array of urls
        $start = $current - $plusMinus;
        $end = $current + $plusMinus;
        if ($start < 1) { // if less than 1, add them to the upper end
            $end += abs($start) + 1;
            $startModified = true;
        }
        if ($end > $total) { // if more than total, add them to the lower end
            if (!isset($startModified)) {
                $start = $start - ($end - $total);
            }
            $end = $total;
        }
        // and finally check again if start goes below 1
        $start = ($start < 1) ? 1 : $start;

        // get the links
        $links = $this->getRangeUrls($start, $end);

        // if needed, add first/last page
        if ($includeFirstLastPage) {
            if (!isset($links[1])) {
                $links[1] = $this->getFirstUrl();
            }
            if (!isset($links[$total])) {
                $links[$total] = $this->getLastUrl();
            }
            // sort to get the first link at the beginning
            ksort($links);
        }
        return $links;
    }
}
