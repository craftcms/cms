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

		$last = $offset + $limit;

		if ($last > $total)
		{
			$last = $total;
		}

		$paginateVariable = new PaginateVariable();
		$paginateVariable->first = $offset + 1;
		$paginateVariable->last = $last;
		$paginateVariable->total = $total;
		$paginateVariable->currentPage = $currentPage;
		$paginateVariable->totalPages = $totalPages;

		// Get the entities
		$criteria->offset = $offset;
		$entities = $criteria->find();

		return array($paginateVariable, $entities);
	}

	/**
	 * Returns a string wrapped in a \Twig_Markup object
	 *
	 * @param $value
	 * @return \Twig_Markup
	 */
	public static function getRaw($value)
	{
		$charset = craft()->templates->getTwig()->getCharset();
		return new \Twig_Markup($value, $charset);
	}
}
