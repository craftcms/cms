<?php
namespace Blocks;

/**
 *
 */
class Product
{
	const Blocks    = 'Blocks';
	const BlocksPro = 'BlocksPro';

	/**
	 * @param $product
	 * @return string
	 */
	public static function display($product)
	{
		if ($product == self::BlocksPro)
			return 'Blocks Pro';

		return 'Blocks';
	}
}
