<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\helpers;

use Craft;

/**
 * Search helper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SearchHelper
{
	// Constants
	// =========================================================================

	// Reformat this?
	const DEFAULT_STOP_WORDS = "a's able about above according accordingly across actually after afterwards again against ain't all allow allows almost alone along already also although always am among amongst an and another any anybody anyhow anyone anything anyway anyways anywhere apart appear appreciate appropriate are aren't around as aside ask asking associated at available away awfully be became because become becomes becoming been before beforehand behind being believe below beside besides best better between beyond both brief but by c'mon c's came can can't cannot cant cause causes certain certainly changes clearly co com come comes concerning consequently consider considering contain containing contains corresponding could couldn't course currently definitely described despite did didn't different do does doesn't doing don't done down downwards during each edu eg eight either else elsewhere enough entirely especially et etc even ever every everybody everyone everything everywhere ex exactly example except far few fifth first five followed following follows for former formerly forth four from further furthermore get gets getting given gives go goes going gone got gotten greetings had hadn't happens hardly has hasn't have haven't having he he's hello help hence her here here's hereafter hereby herein hereupon hers herself hi him himself his hither hopefully how howbeit however i'd i'll i'm i've ie if ignored immediate in inasmuch inc indeed indicate indicated indicates inner insofar instead into inward is isn't it it'd it'll it's its itself just keep keeps kept know known knows last lately later latter latterly least less lest let let's like liked likely little look looking looks ltd mainly many may maybe me mean meanwhile merely might more moreover most mostly much must my myself name namely nd near nearly necessary need needs neither never nevertheless new next nine no nobody non none noone nor normally not nothing novel now nowhere obviously of off often oh ok okay old on once one ones only onto or other others otherwise ought our ours ourselves out outside over overall own particular particularly per perhaps placed please plus possible presumably probably provides que quite qv rather rd re really reasonably regarding regardless regards relatively respectively right said same saw say saying says second secondly see seeing seem seemed seeming seems seen self selves sensible sent serious seriously seven several shall she should shouldn't since six so some somebody somehow someone something sometime sometimes somewhat somewhere soon sorry specified specify specifying still sub such sup sure t's take taken tell tends th than thank thanks thanx that that's thats the their theirs them themselves then thence there there's thereafter thereby therefore therein theres thereupon these they they'd they'll they're they've think third this thorough thoroughly those though three through throughout thru thus to together too took toward towards tried tries truly try trying twice two un under unfortunately unless unlikely until unto up upon us use used useful uses using usually value various very via viz vs want wants was wasn't way we we'd we'll we're we've welcome well went were weren't what what's whatever when whence whenever where where's whereafter whereas whereby wherein whereupon wherever whether which while whither who who's whoever whole whom whose why will willing wish with within without won't wonder would wouldn't yes yet you you'd you'll you're you've your yours yourself yourselves zero";

	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private static $_ftMinWordLength;

	/**
	 * @var
	 */
	private static $_ftStopWords;

	// Public Methods
	// =========================================================================

	/**
	 * Normalizes search keywords.
	 *
	 * @param string $str    The dirty keywords.
	 * @param array  $ignore Ignore words to strip out.
	 *
	 * @return string The cleansed keywords.
	 */
	public static function normalizeKeywords($str, $ignore = [])
	{
		// Flatten
		if (is_array($str))
		{
			$str = StringHelper::toString($str, ' ');
		}

		// Get rid of tags
		$str = strip_tags($str);

		// Convert non-breaking spaces entities to regular ones
		$str = str_replace(['&nbsp;', '&#160;', '&#xa0;'] , ' ', $str);

		// Get rid of entities
		$str = html_entity_decode($str, ENT_QUOTES, StringHelper::UTF8);

		// Remove punctuation and diacritics
		$str = strtr($str, static::_getCharMap());

		// Normalize to lowercase
		$str = StringHelper::toLowerCase($str);

		// Remove ignore-words?
		if (is_array($ignore) && !empty($ignore))
		{
			foreach ($ignore as $word)
			{
				$word = preg_quote(static::normalizeKeywords($word), '/');
				$str  = preg_replace("/\b{$word}\b/u", '', $str);
			}
		}

		// Strip out new lines and superfluous spaces
		$str = preg_replace('/[\n\r]+/u', ' ', $str);
		$str = preg_replace('/\s{2,}/u', ' ', $str);

		// Trim white space
		$str = trim($str);

		return $str;
	}

	/**
	 * Returns the FULLTEXT minimum word length.
	 *
	 * @todo Get actual value from DB
	 * @return int
	 */
	public static function getMinWordLength()
	{
		if (!isset(static::$_ftMinWordLength))
		{
			static::$_ftMinWordLength = 4;
		}

		return static::$_ftMinWordLength;
	}

	/**
	 * Returns the FULLTEXT stop words.
	 *
	 * @todo Make this customizable from the config settings
	 * @return array
	 */
	public static function getStopWords()
	{
		if (!isset(static::$_ftStopWords))
		{
			$words = explode(' ', static::DEFAULT_STOP_WORDS);

			foreach ($words as &$word)
			{
				$word = static::normalizeKeywords($word);
			}

			static::$_ftStopWords = $words;
		}

		return static::$_ftStopWords;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Get array of chars to be used for conversion.
	 *
	 * @return array
	 */
	private static function _getCharMap()
	{
		// Keep local copy
		static $map = [];

		if (empty($map))
		{
			// This will replace accented chars with non-accented chars
			foreach (StringHelper::getAsciiCharMap() as $asciiChar => $charsArray)
			{
				foreach ($charsArray as $char)
				{
					$map[$char] = $asciiChar;
				}
			}

			// Replace punctuation with a space
			foreach (static::_getPunctuation() as $value)
			{
				$map[$value] = ' ';
			}
		}

		// Return the char map
		return $map;
	}

	/**
	 * Returns the asciiPunctuation array.
	 *
	 * @return array
	 */
	private static function _getPunctuation()
	{
		// Keep local copy
		static $asciiPunctuation = [];

		if (empty($asciiPunctuation))
		{
			$asciiPunctuation =  [
				'!', '"', '#',  '&',  '\'', '(', ')', '*', '+', ',', '-', '.', '/',  ':',  ';', '<', '>', '?',
				'@', '[', '\\', ']',  '^',  '{', '|', '}', '~', '¡', '¢', '£', '¤',  '¥',  '¦', '§', '¨', '©',
				'ª', '«', '¬',  '®',  '¯',  '°', '±', '²', '³', '´', 'µ', '¶', '·',  '¸',  '¹', 'º', '»', '¼',
				'½', '¾', '¿',  '×',  'ƒ',  'ˆ', '˜', '–', '—', '―', '‘', '’', '‚',  '“',  '”', '„', '†', '‡',
				'•', '‣', '…',  '‰',  '′',  '″', '‹', '›', '‼', '‾', '⁄', '€', '™',  '←',  '↑', '→', '↓', '↔',
				'↵', '⇐', '⇑',  '⇒',  '⇓',  '⇔', '∀', '∂', '∃', '∅', '∇', '∈', '∉',  '∋',  '∏', '∑', '−', '∗',
				'√', '∝', '∞',  '∠',  '∧',  '∨', '∩', '∪', '∫', '∴', '∼', '≅', '≈',  '≠',  '≡', '≤', '≥', '⊂',
				'⊃', '⊄', '⊆',  '⊇',  '⊕',  '⊗', '⊥', '⋅', '⌈', '⌉', '⌊', '⌋', '〈', '〉', '◊', '♠', '♣', '♥',
				'♦'
			];
		}

		return $asciiPunctuation;
	}
}
