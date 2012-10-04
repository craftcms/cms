<?php
namespace Blocks;

/**
 *
 */
class LocalizationService extends BaseApplicationComponent
{
	private static $_languages = array(
		'aa',    'af',    'ak',  'am',    'ar',    'ar_001', 'as',    'az',  'bas',     'be',
		'bem',   'bg',    'bm',  'bn',    'bo',    'br',     'bs',    'byn', 'ca',      'cch',
		'chr',   'cs',    'cy',  'da',    'de',    'de_at',  'de_ch', 'dua', 'dv',      'dz',
		'ee',    'el',    'en',  'en_au', 'en_ca', 'en_gb',  'en_us', 'eo',  'es',      'es_419',
		'es_es', 'et',    'eu',  'ewo',   'fa',    'ff',     'fi',    'fil', 'fo',      'fr',
		'fr_ca', 'fr_ch', 'fur', 'ga',    'gaa',   'gd',     'gez',   'gl',  'gsw',     'gu',
		'gv',    'ha',    'haw', 'he',    'hi',    'hr',     'hu',    'hy',  'ia',      'id',
		'ig',    'ii',    'is',  'it',    'iu',    'ja',     'ka',    'kab', 'kaj',     'kam',
		'kcg',   'ki',    'kk',  'kl',    'km',    'kn',     'ko',    'kok', 'kpe',     'ku',
		'kw',    'ky',    'lg',  'ln',    'lo',    'lt',     'lu',    'luo', 'lv',      'mas',
		'mg',    'mi',    'mk',  'ml',    'mn',    'mo',     'mr',    'ms',  'mt',      'my',
		'nb',    'nd',    'nds', 'ne',    'nl',    'nl_be',  'nn',    'no',  'nr',      'nso',
		'ny',    'nyn',   'oc',  'om',    'or',    'pa',     'pl',    'ps',  'pt',      'pt_br',
		'pt_pt', 'rm',    'rn',  'ro',    'ru',    'rw',     'sa',    'sah', 'se',      'sg',
		'sh',    'si',    'sid', 'sk',    'sl',    'sn',     'so',    'sq',  'sr',      'ss',
		'st',    'sv',    'sw',  'syr',   'ta',    'te',     'tg',    'th',  'ti',      'tig',
		'tl',    'tn',    'to',  'tr',    'ts',    'tt',     'ug',    'uk',  'ur',      'uz',
		'vai',   've',    'vi',  'wal',   'wo',    'xh',     'yo',    'zh',  'zh_hans', 'zh_hant',
		'zu'
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
	 * Returns a list of language ids from the languages directory that Blocks is translated into.
	 *
	 * @return mixed
	 */
	public function getTranslatedLanguages()
	{
		if (!$this->_translatedLanguages)
		{
			$this->_translatedLanguages = array();

			$path = blx()->path->getCpTranslationsPath();
			$folders = IOHelper::getFolderContents($path, false, ".*\.php");

			if (is_array($folders) && count($folders) > 0)
			{
				foreach ($folders as $dir)
				{
					$this->_translatedLanguages[] = IOHelper::getFileName($dir, false);
				}
			}

			if (!in_array('en_us', $this->_translatedLanguages))
			{
				$this->_translatedLanguages[] = 'en_us';
			}

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
		{
			return Locale::getInstance($languageCode);
		}

		return false;
	}
}
