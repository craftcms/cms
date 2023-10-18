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
 * @since 3.0.0
 */
class Paginate extends BaseObject
{
    /**
     * Creates a new instance based on a Paginator object
     *
     * @param Paginator $paginator
     * @return static
     * @since 3.1.19
     */
    public static function create(Paginator $paginator): self
    {
        $pageResults = $paginator->getPageResults();
        $pageOffset = $paginator->getPageOffset();

        return Craft::createObject([
            'class' => static::class,
            'first' => $pageOffset + 1,
            'last' => $pageOffset + count($pageResults),
            'total' => $paginator->getTotalResults(),
            'currentPage' => $paginator->getCurrentPage(),
            'totalPages' => $paginator->getTotalPages(),
        ]);
    }

    /**
     * @var int
     */
    public int $first;

    /**
     * @var int
     */
    public int $last;

    /**
     * @var int
     */
    public int $total = 0;

    /**
     * @var int
     */
    public int $currentPage;

    /**
     * @var int
     */
    public int $totalPages = 0;

    /**
     * @var string
     * @since 3.7.64
     */
    public string $pageTrigger;

    /**
     * @var string Base path
     * @see getBasePath()
     * @see setBasePath()
     */
    private string $_basePath;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (!isset($this->pageTrigger)) {
            $this->pageTrigger = Craft::$app->getRequest()->getIsCpRequest()
                ? 'p'
                : Craft::$app->getConfig()->getGeneral()->getPageTrigger();
        }
    }

    /**
     * Returns the base path that should be used for pagination URLs.
     *
     * @return string
     */
    public function getBasePath(): string
    {
        if (isset($this->_basePath)) {
            return $this->_basePath;
        }
        return $this->_basePath = Craft::$app->getRequest()->getPathInfo();
    }

    /**
     * Sets the base path that should be used for pagination URLs.
     *
     * @param string $basePath
     * @since 3.1.28
     */
    public function setBasePath(string $basePath): void
    {
        $this->_basePath = $basePath;
    }

    /**
     * Returns the URL to a specific page
     *
     * @param int $page
     * @return string|null
     */
    public function getPageUrl(int $page): ?string
    {
        if ($page < 1 || $page > $this->totalPages) {
            return null;
        }

        $useQueryParam = str_starts_with($this->pageTrigger, '?');

        $path = $this->getBasePath();

        // If not using a query param, append the page to the path
        if (!$useQueryParam && $page != 1) {
            if ($path) {
                $path .= '/';
            }

            $path .= $this->pageTrigger . $page;
        }

        // Build the URL with the same query string as the current request
        $url = UrlHelper::url($path, Craft::$app->getRequest()->getQueryStringWithoutPath());

        // If using a query param, append or remove it
        if ($useQueryParam) {
            $param = trim($this->pageTrigger, '?=');
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
    public function getFirstUrl(): ?string
    {
        return $this->getPageUrl(1);
    }

    /**
     * Returns the URL to the next page.
     *
     * @return string|null
     */
    public function getLastUrl(): ?string
    {
        return $this->getPageUrl($this->totalPages);
    }

    /**
     * Returns the URL to the previous page.
     *
     * @return string|null
     */
    public function getPrevUrl(): ?string
    {
        return $this->getPageUrl($this->currentPage - 1);
    }

    /**
     * Returns the URL to the next page.
     *
     * @return string|null
     */
    public function getNextUrl(): ?string
    {
        return $this->getPageUrl($this->currentPage + 1);
    }

    /**
     * Returns previous page URLs up to a certain distance from the current page.
     *
     * @param int|null $dist
     * @return array
     */
    public function getPrevUrls(?int $dist = null): array
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
    public function getNextUrls(?int $dist = null): array
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
     * @return string[]
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
     * Returns a dynamic range of page URLs that surround (and include) the current page.
     *
     * @param int $max The maximum number of links to return
     * @return string[]
     */
    public function getDynamicRangeUrls(int $max = 10): array
    {
        $start = max(1, $this->currentPage - floor($max / 2));
        $end = min($this->totalPages, $start + $max - 1);
        if ($end - $start < $max) {
            $start = max(1, $end - $max + 1);
        }
        return $this->getRangeUrls($start, $end);
    }
}
