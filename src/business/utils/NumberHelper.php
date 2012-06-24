<?php
namespace Blocks;

/**
 *
 */
class NumberHelper
{
	private static $_numberWordMap = array(
		1 => 'one',
		2 => 'two',
		3 => 'three',
		4 => 'four',
		5 => 'five',
		6 => 'six',
		7 => 'seven',
		8 => 'eight',
		9 => 'nine'
	);

	/**
	 * Returns the "word" version of a number
	 * @param int $num The number
	 * @return string The number word, or the original number if it's >= 10
	 */
	public static function word($num)
	{
		if (isset(self::$_numberWordMap[$num]))
			return self::$_numberWordMap[$num];

		return (string)$num;
	}


	/**
	 * Returns the uppercase alphabetic version of a number
	 * @param int $num The number
	 * @return string The alphabetic version of the number
	 */
	function upperAlpha($num)
	{
		$num--;
		$alpha = '';

		while ($num >= 0)
		{
			$ascii = ($num % 26) + 65;
			$alpha = chr($ascii) . $alpha;

			$num = intval($num / 26) - 1;
		}

		return $alpha;
	}

	/**
	 * Returns the lowercase alphabetic version of a number
	 * @param int $num The number
	 * @return string The alphabetic version of the number
	 */
	function lowerAlpha($num)
	{
		$alpha = self::upperAlpha($num);
		return strtolower($alpha);
	}

	/**
	 * Returns the uppercase roman numeral version of a number
	 * @param int $num The number
	 * @return string The roman numeral version of the number
	 */
	function upperRoman($num)
	{
		$roman = '';

		$map = array(
			'M'  => 1000,
			'CM' => 900,
			'D'  => 500,
			'CD' => 400,
			'C'  => 100,
			'XC' => 90,
			'L'  => 50,
			'XL' => 40,
			'X'  => 10,
			'IX' => 9,
			'V'  => 5,
			'IV' => 4,
			'I'  => 1
		);

		foreach ($map as $k => $v)
		{
			while ($num >= $v) {
				$roman .= $k;
				$num -= $v;
			}
		}

		return $roman;
	}

	/**
	 * Returns the lowercase roman numeral version of a number
	 * @param int $num The number
	 * @return string The roman numeral version of the number
	 */
	function lowerRoman($num)
	{
		$roman = self::upperRoman($num);
		return strtolower($roman);
	}

}
