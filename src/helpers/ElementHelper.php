<?php
namespace Craft;

/**
 *
 */
class ElementHelper
{
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
}
