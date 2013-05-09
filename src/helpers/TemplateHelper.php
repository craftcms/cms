<?php
namespace Craft;

/**
 *
 */
class TemplateHelper
{
	/**
	 * Paginates an ElementCriteriaModel instance.
	 */
	public static function paginateCriteria(ElementCriteriaModel $criteria)
	{
		$currentPage = craft()->request->getPageNum();
		$limit = $criteria->limit;
		$total = $criteria->total();
		$totalPages = ceil($total / $limit);

		if ($currentPage > $totalPages)
		{
			$currentPage = $totalPages;
		}

		$offset = $limit * ($currentPage - 1);

		$path = craft()->request->getPath();
		$pageUrlPrefix = ($path ? $path.'/' : '').craft()->config->get('pageTrigger');

		$last = $offset + $limit;

		if ($last > $total)
		{
			$last = $total;
		}

		$info = array(
			'first'       => $offset + 1,
			'last'        => $last,
			'total'       => $total,
			'currentPage' => $currentPage,
			'totalPages'  => $totalPages,
			'prevUrl'     => ($currentPage > 1           ? UrlHelper::getUrl($pageUrlPrefix.($currentPage-1)) : null),
			'nextUrl'     => ($currentPage < $totalPages ? UrlHelper::getUrl($pageUrlPrefix.($currentPage+1)) : null),
		);

		// Get the entities
		$criteria->offset = $offset;
		$entities = $criteria->find();

		return array($info, $entities);
	}
}
