<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\helpers;

use craft\app\Craft;
use craft\app\models\ElementCriteria as ElementCriteriaModel;
use craft\app\variables\Paginate;

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
	 * Paginates an ElementCriteriaModel instance.
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return array
	 */
	public static function paginateCriteria(ElementCriteriaModel $criteria)
	{
		$currentPage = Craft::$app->request->getPageNum();
		$limit = $criteria->limit;
		$total = $criteria->total() - $criteria->offset;
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
		if ($criteria->offset)
		{
			$offset += $criteria->offset;
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
		$criteria = $criteria->copy();
		$criteria->offset = $offset;
		$elements = $criteria->find();

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
		$charset = Craft::$app->templates->getTwig()->getCharset();
		return new \Twig_Markup($value, $charset);
	}
}
