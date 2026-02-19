<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Variables;

use Craft;
use craft\db\Paginator;
use craft\helpers\UrlHelper;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Component;

final class Paginate extends Component
{
    public string $basePath {
        get => $this->getBasePath();
        set => $this->setBasePath($value);
    }

    public int $first;

    public int $last;

    public int $total = 0;

    public int $currentPage;

    public int $totalPages = 0;

    public string $pageTrigger;

    /**
     * @var string Base path
     *
     * @see getBasePath()
     * @see setBasePath()
     */
    private string $_basePath;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->pageTrigger ??= request()->isCpRequest()
            ? 'p'
            : Cms::config()->getPageTrigger();
    }

    public static function create(Paginator $paginator): self
    {
        $pageResults = $paginator->getPageResults();
        $pageOffset = $paginator->getPageOffset();

        return new self([
            'first' => $pageOffset + 1,
            'last' => $pageOffset + count($pageResults),
            'total' => $paginator->getTotalResults(),
            'currentPage' => $paginator->getCurrentPage(),
            'totalPages' => $paginator->getTotalPages(),
        ]);
    }

    public function getBasePath(): string
    {
        return $this->_basePath ?? ($this->_basePath = Craft::$app->getRequest()->getPathInfo());
    }

    public function setBasePath(string $basePath): void
    {
        $this->_basePath = $basePath;
    }

    public function getPageUrl(int $page): ?string
    {
        if ($page < 1 || $page > $this->totalPages) {
            return null;
        }

        $useQueryParam = str_starts_with($this->pageTrigger, '?');

        $path = $this->getBasePath();

        // If not using a query param, append the page to the path
        if (! $useQueryParam && $page !== 1) {
            if ($path) {
                $path .= '/';
            }

            $path .= $this->pageTrigger.$page;
        }

        // Build the URL with the same query string as the current request
        $url = UrlHelper::url($path, Craft::$app->getRequest()->getQueryStringWithoutPath());

        // If using a query param, append or remove it
        if ($useQueryParam) {
            $param = trim($this->pageTrigger, '?=');
            if ($page !== 1) {
                $url = UrlHelper::urlWithParams($url, [$param => $page]);
            } else {
                $url = UrlHelper::removeParam($url, $param);
            }
        }

        return $url;
    }

    public function getFirstUrl(): ?string
    {
        return $this->getPageUrl(1);
    }

    public function getLastUrl(): ?string
    {
        return $this->getPageUrl($this->totalPages);
    }

    public function getPrevUrl(): ?string
    {
        return $this->getPageUrl($this->currentPage - 1);
    }

    public function getNextUrl(): ?string
    {
        return $this->getPageUrl($this->currentPage + 1);
    }

    public function getPrevUrls(?int $dist = null): array
    {
        if ($dist !== null) {
            $start = $this->currentPage - $dist;
        } else {
            $start = 1;
        }

        return $this->getRangeUrls($start, $this->currentPage - 1);
    }

    public function getNextUrls(?int $dist = null): array
    {
        if ($dist !== null) {
            $end = $this->currentPage + $dist;
        } else {
            $end = $this->totalPages;
        }

        return $this->getRangeUrls($this->currentPage + 1, $end);
    }

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
