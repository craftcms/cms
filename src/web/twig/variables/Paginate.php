<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;
use craft\helpers\UrlHelper;

/**
 * Paginate variable class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Paginate
{
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

    // Public Methods
    // =========================================================================

    /**
     * Returns the URL to a specific page
     *
     * @param int $page
     * @return string|null
     */
    public function getPageUrl(int $page)
    {
        if ($page >= 1 && $page <= $this->totalPages) {
            $path = Craft::$app->getRequest()->getPathInfo();
            $params = [];

            if ($page != 1) {
                $pageTrigger = Craft::$app->getConfig()->getGeneral()->pageTrigger;

                if (!is_string($pageTrigger) || $pageTrigger === '') {
                    $pageTrigger = 'p';
                }

                // Is this query string-based pagination?
                if ($pageTrigger[0] === '?') {
                    $pageTrigger = trim($pageTrigger, '?=');

                    // Avoid conflict with the path param
                    $pathParam = Craft::$app->getConfig()->getGeneral()->pathParam;
                    if ($pageTrigger === $pathParam) {
                        $pageTrigger = $pathParam === 'p' ? 'pg' : 'p';
                    }

                    $params = [$pageTrigger => $page];
                } else {
                    if ($path) {
                        $path .= '/';
                    }

                    $path .= $pageTrigger.$page;
                }
            }

            return UrlHelper::url($path, $params);
        }

        return null;
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
}
