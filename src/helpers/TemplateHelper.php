<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\helpers;

use Craft;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\web\twig\variables\Paginate;

/**
 * Class TemplateHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class TemplateHelper
{
	// Public Methods
	// =========================================================================

	/**
	 * Paginates an element query's results
	 *
	 * @param ElementQueryInterface $query
	 *
	 * @return array
	 */
	public static function paginateCriteria(ElementQueryInterface $query)
	{
		$currentPage = Craft::$app->getRequest()->getPageNum();
		$limit = $query->limit;
		$total = $query->count() - $query->offset;

		// If they specified limit as null or 0 (for whatever reason), just assume it's all going to be on one page.
		if (!$limit)
		{
			$limit = $total;
		}

		$totalPages = ceil($total / $limit);

		$paginateVariable = new Paginate();

		if ($totalPages == 0)
		{
			return [$paginateVariable, []];
		}

		if ($currentPage > $totalPages)
		{
			$currentPage = $totalPages;
		}

		$offset = $limit * ($currentPage - 1);

		// Is there already an offset set?
		if ($query->offset)
		{
			$offset += $query->offset;
		}

		$last = $offset + $limit;

		if ($last > $total)
		{
			$last = $total;
		}

		$paginateVariable->first = $offset + 1;
		$paginateVariable->last = $last;
		$paginateVariable->total = $total;
		$paginateVariable->currentPage = $currentPage;
		$paginateVariable->totalPages = $totalPages;

		// Copy the criteria, set the offset, and get the elements
		$query = clone $query;
		$query->offset = $offset;
		$elements = $query->all();

		return [$paginateVariable, $elements];
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
		return new \Twig_Markup($value, Craft::$app->charset);
	}
}
