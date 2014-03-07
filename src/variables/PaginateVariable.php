<?php
namespace Craft;

/**
 * Paginate variable class
 */
class PaginateVariable
{
	public $first;
	public $last;
	public $total;
	public $currentPage;
	public $totalPages;

	/**
	 * Returns the URL to a specific page
	 *
	 * @return string|null
	 */
	public function getPageUrl($page)
	{
		if ($page >= 1 && $page <= $this->totalPages)
		{
			$path = craft()->request->getPath();

			if ($page != 1)
			{
				if ($path)
				{
					$path .= '/';
				}

				$path .= craft()->config->get('pageTrigger').$page;
			}

			return UrlHelper::getUrl($path);
		}
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
		return $this->getPageUrl($this->currentPage-1);
	}

	/**
	 * Returns the URL to the next page.
	 *
	 * @return string|null
	 */
	public function getNextUrl()
	{
		return $this->getPageUrl($this->currentPage+1);
	}

	/**
	 * Returns previous page URLs up to a certain distance from the current page.
	 *
	 * @param int $dist
	 * @return array
	 */
	public function getPrevUrls($dist = null)
	{
		if ($dist)
		{
			$start = $this->currentPage - $dist;
		}
		else
		{
			$start = 1;
		}

		return $this->getRangeUrls($start, $this->currentPage - 1);
	}

	/**
	 * Returns next page URLs up to a certain distance from the current page.
	 *
	 * @param int $dist
	 * @return array
	 */
	public function getNextUrls($dist = null)
	{
		if ($dist)
		{
			$end = $this->currentPage + $dist;
		}
		else
		{
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
	public function getRangeUrls($start, $end)
	{
		if ($start < 1)
		{
			$start = 1;
		}

		if ($end > $this->totalPages)
		{
			$end = $this->totalPages;
		}

		$urls = array();

		for ($page = $start; $page <= $end; $page++)
		{
			$urls[$page] = $this->getPageUrl($page);
		}

		return $urls;
	}
}