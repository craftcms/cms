<?php
namespace Craft;

/**
 * Class TemplateHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.helpers
 * @since     1.0
 */
class TemplateHelper
{
	// Public Methods
	// =========================================================================

	/**
	 * Paginates an ElementCriteriaModel instance.
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return array
	 */
	public static function paginateCriteria(ElementCriteriaModel $criteria)
	{
		$currentPage = craft()->request->getPageNum();
		$limit = $criteria->limit;
		$total = $criteria->total() - $criteria->offset;
		$totalPages = ceil($total / $limit);

		if ($currentPage > $totalPages)
		{
			$currentPage = $totalPages;
		}

		$offset = $limit * ($currentPage - 1);

		// Is there already an offset set?
		if ($criteria->offset)
		{
			$offset += $criteria->offset;
		}

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

		// Copy the criteria, set the offset, and get the elements
		$criteria = $criteria->copy();
		$criteria->offset = $offset;
		$elements = $criteria->find();

		return array($paginateVariable, $elements);
	}

	/**
	 * Returns a string wrapped in a \Twig_Markup object
	 *
	 * @param $value
	 *
	 * @return \Twig_Markup
	 */
	public static function getRaw($value)
	{
		$charset = craft()->templates->getTwig()->getCharset();
		return new \Twig_Markup($value, $charset);
	}
}
