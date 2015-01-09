<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\helpers;

use Craft;
use craft\app\errors\Exception;
use craft\app\models\BaseElementModel;

/**
 * Class ElementHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ElementHelper
{
	// Public Methods
	// =========================================================================

	/**
	 * Sets a valid slug on a given element.
	 *
	 * @param BaseElementModel $element
	 *
	 * @return null
	 */
	public static function setValidSlug(BaseElementModel $element)
	{
		$slug = $element->slug;

		if (!$slug)
		{
			// Create a slug for them, based on the element's title.
			// Replace periods, underscores, and hyphens with spaces so they get separated with the slugWordSeparator
			// to mimic the default JavaScript-based slug generation.
			$slug = str_replace(['.', '_', '-'], ' ', $element->title);
		}

		$element->slug = static::createSlug($slug);
	}

	/**
	 * Creates a slug based on a given string.
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	public static function createSlug($str)
	{
		// Remove HTML tags
		$slug = preg_replace('/<(.*?)>/u', '', $str);

		// Remove inner-word punctuation.
		$slug = preg_replace('/[\'"‘’“”\[\]\(\)\{\}:]/u', '', $slug);

		if (Craft::$app->config->get('allowUppercaseInSlug') === false)
		{
			// Make it lowercase
			$slug = StringHelper::toLowerCase($slug);
		}

		// Get the "words". Split on anything that is not alphanumeric, or a period, underscore, or hyphen.
		preg_match_all('/[\p{L}\p{N}\._-]+/u', $slug, $words);
		$words = ArrayHelper::filterEmptyStringsFromArray($words[0]);
		$slug = implode(Craft::$app->config->get('slugWordSeparator'), $words);

		return $slug;
	}

	/**
	 * Sets the URI on an element using a given URL format, tweaking its slug if necessary to ensure it's unique.
	 *
	 * @param BaseElementModel $element
	 *
	 * @throws Exception
	 */
	public static function setUniqueUri(BaseElementModel $element)
	{
		$urlFormat = $element->getUrlFormat();

		// No URL format, no URI.
		if (!$urlFormat)
		{
			$element->uri  = null;
			return;
		}

		// No slug, or a URL format with no {slug}, just parse the URL format and get on with our lives
		if (!$element->slug || !static::doesUrlFormatHaveSlugTag($urlFormat))
		{
			$element->uri = Craft::$app->templates->renderObjectTemplate($urlFormat, $element);
			return;
		}

		$uniqueUriConditions = ['and',
			'locale = :locale',
			'uri = :uri'
		];

		$uniqueUriParams = [
			':locale' => $element->locale
		];

		if ($element->id)
		{
			$uniqueUriConditions[] = 'elementId != :elementId';
			$uniqueUriParams[':elementId'] = $element->id;
		}

		$slugWordSeparator = Craft::$app->config->get('slugWordSeparator');
		$maxSlugIncrement = Craft::$app->config->get('maxSlugIncrement');

		for ($i = 0; $i < $maxSlugIncrement; $i++)
		{
			$testSlug = $element->slug;

			if ($i > 0)
			{
				$testSlug .= $slugWordSeparator.$i;
			}

			$originalSlug = $element->slug;
			$element->slug = $testSlug;

			$testUri = Craft::$app->templates->renderObjectTemplate($urlFormat, $element);

			// Make sure we're not over our max length.
			if (strlen($testUri) > 255)
			{
				// See how much over we are.
				$overage = strlen($testUri) - 255;

				// Do we have anything left to chop off?
				if (strlen($overage) > strlen($element->slug) - strlen($slugWordSeparator.$i))
				{
					// Chop off the overage amount from the slug
					$testSlug = $element->slug;
					$testSlug = substr($testSlug, 0, strlen($testSlug) - $overage);

					// Update the slug
					$element->slug = $testSlug;

					// Let's try this again.
					$i -= 1;
					continue;
				}
				else
				{
					// We're screwed, blow things up.
					throw new Exception(Craft::t('The maximum length of a URI is 255 characters.'));
				}
			}

			$uniqueUriParams[':uri'] = $testUri;

			$totalElements = Craft::$app->db->createCommand()
				->select('count(id)')
				->from('elements_i18n')
				->where($uniqueUriConditions, $uniqueUriParams)
				->queryScalar();

			if ($totalElements ==  0)
			{
				// OMG!
				$element->slug = $testSlug;
				$element->uri = $testUri;
				return;
			}
			else
			{
				$element->slug = $originalSlug;
			}
		}

		throw new Exception(Craft::t('Could not find a unique URI for this element.'));
	}

	/**
	 * Returns whether a given URL format has a proper {slug} tag.
	 *
	 * @param string $urlFormat
	 *
	 * @return bool
	 */
	public static function doesUrlFormatHaveSlugTag($urlFormat)
	{
		$element = (object) ['slug' => StringHelper::randomString()];
		$uri = Craft::$app->templates->renderObjectTemplate($urlFormat, $element);

		return (strpos($uri, $element->slug) !== false);
	}

	/**
	 * Returns whether the given element is editable by the current user, taking user locale permissions into account.
	 *
	 * @param BaseElementModel $element
	 *
	 * @return bool
	 */
	public static function isElementEditable(BaseElementModel $element)
	{
		if ($element->isEditable())
		{
			if (Craft::$app->isLocalized())
			{
				foreach ($element->getLocales() as $localeId => $localeInfo)
				{
					if (is_numeric($localeId) && is_string($localeInfo))
					{
						$localeId = $localeInfo;
					}

					if (Craft::$app->getUser()->checkPermission('editLocale:'.$localeId))
					{
						return true;
					}
				}
			}
			else
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the editable locale IDs for a given element, taking user locale permissions into account.
	 *
	 * @param BaseElementModel $element
	 *
	 * @return array
	 */
	public static function getEditableLocaleIdsForElement(BaseElementModel $element)
	{
		$localeIds = [];

		if ($element->isEditable())
		{
			if (Craft::$app->isLocalized())
			{
				foreach ($element->getLocales() as $localeId => $localeInfo)
				{
					if (is_numeric($localeId) && is_string($localeInfo))
					{
						$localeId = $localeInfo;
					}

					if (Craft::$app->getUser()->checkPermission('editLocale:'.$localeId))
					{
						$localeIds[] = $localeId;
					}
				}
			}
			else
			{
				$localeIds[] = Craft::$app->i18n->getPrimarySiteLocaleId();
			}
		}

		return $localeIds;
	}
}
