<?php
namespace Craft;

/**
 *
 */
class ElementHelper
{
	/**
	 * Sets a valid slug on a given element.
	 *
	 * @static
	 * @param BaseElementModel $element
	 */
	public static function setValidSlug(BaseElementModel $element)
	{
		$slug = $element->slug;

		if (!$slug)
		{
			$slug = $element->title;
		}

		// Remove HTML tags
		$slug = preg_replace('/<(.*?)>/u', '', $slug);

		// Remove inner-word punctuation.
		$slug = preg_replace('/[\'"‘’“”]/u', '', $slug);

		// Make it lowercase
		$slug = mb_strtolower($slug, 'UTF-8');

		// Get the "words".  Split on anything that is not a unicode letter or number.
		// Periods are OK too.
		preg_match_all('/[\p{L}\p{N}\.]+/u', $slug, $words);
		$words = ArrayHelper::filterEmptyStringsFromArray($words[0]);
		$slug = implode('-', $words);

		$element->slug = $slug;
	}

	/**
	 * Sets the URI on an element using a given URL format,
	 * tweaking its slug if necessary to ensure it's unique.
	 *
	 * @static
	 * @param BaseElementModel $element
	 */
	public static function setUniqueUri(BaseElementModel $element)
	{
		$urlFormat = $element->getUrlFormat();

		if (!$element->slug || !$urlFormat)
		{
			$element->uri  = null;
			return;
		}

		$uniqueUriConditions = array('and',
			'locale = :locale',
			'uri = :uri'
		);

		$uniqueUriParams = array(
			':locale' => $element->locale
		);

		if ($element->id)
		{
			$uniqueUriConditions[] = 'elementId != :elementId';
			$uniqueUriParams[':elementId'] = $element->id;
		}

		for ($i = 0; $i < 100; $i++)
		{
			$testSlug = $element->slug;

			if ($i > 0)
			{
				$testSlug .= '-'.$i;
			}

			$originalSlug = $element->slug;
			$element->slug = $testSlug;

			$testUri = craft()->templates->renderObjectTemplate($urlFormat, $element);

			// Make sure we're not over our max length.
			if (strlen($testUri) > 255)
			{
				// See how much over we are.
				$overage = strlen($testUri) - 255;

				// Do we have anything left to chop off?
				if (strlen($overage) > strlen($element->slug) - strlen('-'.$i))
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

			$totalElements = craft()->db->createCommand()
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
	 * @static
	 * @param string $urlFormat
	 */
	public static function doesUrlFormatHaveSlugTag($urlFormat)
	{
		$element = (object) array('slug' => StringHelper::randomString());
		$uri = craft()->templates->renderObjectTemplate($urlFormat, $element);
		return (strpos($uri, $element->slug) !== false);
	}
}
