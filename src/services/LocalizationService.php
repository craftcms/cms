<?php
namespace Blocks;

/**
 *
 */
class LocalizationService extends \CApplicationComponent
{
	private static $_languages = array(
		'aa', 'af', 'agq', 'ak', 'am', 'ar', 'ar_001', 'as', 'asa', 'az', 'bas', 'be', 'bem', 'bez', 'bg', 'bm', 'bn',
		'bo', 'br', 'brx', 'bs', 'byn', 'ca', 'cch', 'cgg', 'chr', 'cs', 'cy', 'da', 'dav', 'de', 'de_at', 'de_ch',
		'dje', 'dua', 'dv', 'dyo', 'dz', 'ebu', 'ee', 'el', 'en', 'en_au', 'en_ca', 'en_gb', 'en_us', 'eo', 'es',
		'es_419', 'es_es', 'et', 'eu', 'ewo', 'fa', 'ff', 'fi', 'fil', 'fo', 'fr', 'fr_ca', 'fr_ch', 'fur', 'ga', 'gaa',
		'gd', 'gez', 'gl', 'gsw', 'gu', 'guz', 'gv', 'ha', 'haw', 'he', 'hi', 'hr', 'hu', 'hy', 'ia', 'id', 'ig', 'ii',
		'is', 'it', 'iu', 'ja', 'jmc', 'ka', 'kab', 'kaj', 'kam', 'kcg', 'kde', 'kea', 'kfo', 'khq', 'ki', 'kk', 'kl',
		'kln', 'km', 'kn', 'ko', 'kok', 'kpe', 'ksb', 'ksf', 'ksh', 'ku', 'kw', 'ky', 'lag', 'lg', 'ln', 'lo', 'lt',
		'lu', 'luo', 'luy', 'lv', 'mas', 'mer', 'mfe', 'mg', 'mgh', 'mi', 'mk', 'ml', 'mn', 'mo', 'mr', 'ms', 'mt',
		'mua', 'my', 'naq', 'nb', 'nd', 'nds', 'ne', 'nl', 'nl_be', 'nmg', 'nn', 'no', 'nr', 'nso', 'nus', 'ny', 'nyn',
		'oc', 'om', 'or', 'pa', 'pl', 'ps', 'pt', 'pt_br', 'pt_pt', 'rm', 'rn', 'ro', 'rof', 'root', 'ru', 'rw', 'rwk',
		'sa', 'sah', 'saq', 'sbp', 'se', 'seh', 'ses', 'sg', 'sh', 'shi', 'si', 'sid', 'sk', 'sl', 'sn', 'so', 'sq',
		'sr', 'ss', 'ssy', 'st', 'sv', 'sw', 'swc', 'syr', 'ta', 'te', 'teo', 'tg', 'th', 'ti', 'tig', 'tl', 'tn', 'to',
		'tr', 'trv', 'ts', 'tt', 'twq', 'tzm', 'ug', 'uk', 'ur', 'uz', 'vai', 've', 'vi', 'vun', 'wae', 'wal', 'wo',
		'xh', 'xog', 'yav', 'yo', 'zh', 'zh_hans', 'zh_hant', 'zu'
	);

	private $_translatedLanguages;

	/**
	 * Gets the supported content languages.
	 *
	 * We're using this instead of Locale::getLocaleIds() because there's a lot of duplicate stuff in there.
	 * This list contains all of the locale IDs with unique display names.
	 *
	 * @return array
	 */
	public function getLanguages()
	{
		return static::$_languages;
	}

	/**
	 * Returns a list of language ids from the languages directory that @@@productDisplay@@@ is translated into.
	 * @return mixed
	 */
	public function getTranslatedLanguages()
	{
		if (!$this->_translatedLanguages)
		{
			$this->_translatedLanguages = array();

			$path = blx()->path->getTranslationsPath();
			$dirs = glob($path.'*.php');

			if (is_array($dirs) && count($dirs) > 0)
			{
				foreach ($dirs as $dir)
				{
					$this->_translatedLanguages[] = pathinfo($dir, PATHINFO_FILENAME);
				}
			}

			if (!in_array('en_us', $this->_translatedLanguages))
					$this->_translatedLanguages[] = 'en_us';

			sort($this->_translatedLanguages);
		}

		return $this->_translatedLanguages;
	}

	/**
	 * @param $languageCode
	 *
	 * @return bool|\CLocale
	 */
	public function getLanguageData($languageCode)
	{
		if (Locale::exists($languageCode))
			return Locale::getInstance($languageCode);

		return false;
	}
}
