<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Variables;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Url;
use Illuminate\Pagination\LengthAwarePaginator;

class Paginate extends Component
{
    public string $basePath {
        get => $this->getBasePath();
        set {
            $this->setBasePath($value);
        }
    }

    public int $first;

    public int $last;

    public int $total = 0;

    public int $currentPage;

    public int $totalPages = 0;

    /**
     * @var string Base path
     *
     * @see getBasePath()
     * @see setBasePath()
     */
    private string $_basePath;

    public static function create(LengthAwarePaginator $paginator): self
    {
        return new self([
            'first' => $paginator->firstItem() ?? 0,
            'last' => $paginator->lastItem() ?? 0,
            'total' => $paginator->total(),
            'currentPage' => $paginator->currentPage(),
            'totalPages' => $paginator->lastPage(),
        ]);
    }

    public function getBasePath(): string
    {
        return $this->_basePath ?? ($this->_basePath = request()->path());
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

        $pageParam = Cms::config()->getPageTriggerParam();
        $params = Arr::except(request()->query(), $pageParam);

        if ($page !== 1) {
            $params[$pageParam] = $page;
        }

        return Url::url($this->getBasePath(), $params);
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
