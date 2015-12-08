<?php
namespace Craft;

/**
 * ElementIndexesService provides APIs for managing element indexes.
 *
 * An instance of ElementIndexesService is globally accessible in Craft via {@link WebApp::elementIndexes `craft()->elementIndexes`}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.services
 * @since     2.5
 */
class ElementIndexesService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	private $_indexSettings;

	// Public Methods
	// =========================================================================

	/**
	 * Returns the element index settings for a given element type.
	 *
	 * @param string $elementTypeClass The element type class
	 *
	 * @return array|null
	 */
	public function getSettings($elementTypeClass)
	{
		if ($this->_indexSettings === null || !array_key_exists($elementTypeClass, $this->_indexSettings))
		{
			$result = craft()->db->createCommand()
				->select('settings')
				->from('elementindexsettings')
				->where('type = :type', array(':type' => $elementTypeClass))
				->queryScalar();

			if ($result)
			{
				$this->_indexSettings[$elementTypeClass] = JsonHelper::decode($result);
			}
			else
			{
				$this->_indexSettings[$elementTypeClass] = null;
			}
		}

		return $this->_indexSettings[$elementTypeClass];
	}

	/**
	 * Saves new element index settings for a given element type.
	 *
	 * @param string $elementTypeClass The element type class
	 * @param array  $newSettings      The new index settings
	 */
	public function saveSettings($elementTypeClass, $newSettings)
	{
		// Get the currently saved settings
		$settings = $this->getSettings($elementTypeClass);
		$elementType = craft()->elements->getElementType($elementTypeClass);
		$baseSources = $this->_normalizeSources($elementType->getSources('index'));

		// Updating the source order?
		if (isset($newSettings['sourceOrder']))
		{
			// Only actually save a custom order if it's different from the default order
			$saveSourceOrder = false;

			if (count($newSettings['sourceOrder']) != count($baseSources))
			{
				$saveSourceOrder = true;
			}
			else
			{
				foreach ($baseSources as $i => $source)
				{
					// Any differences?
					if (
						(array_key_exists('heading', $source) && (
							$newSettings['sourceOrder'][$i][0] != 'heading' ||
							$newSettings['sourceOrder'][$i][1] != $source['heading']
						)) ||
						(array_key_exists('key', $source) && (
							$newSettings['sourceOrder'][$i][0] != 'key' ||
							$newSettings['sourceOrder'][$i][1] != $source['key']
						))
					)
					{
						$saveSourceOrder = true;
						break;
					}
				}
			}

			if ($saveSourceOrder)
			{
				$settings['sourceOrder'] = $newSettings['sourceOrder'];
			}
			else
			{
				unset($settings['sourceOrder']);
			}
		}

		// Updating the source settings?
		if (isset($newSettings['sources']))
		{
			// Merge in the new source settings
			if (!isset($settings['sources']))
			{
				$settings['sources'] = $newSettings['sources'];
			}
			else
			{
				$settings['sources'] = array_merge($settings['sources'], $newSettings['sources']);
			}

			// Prune out any settings for sources that don't exist
			$indexedBaseSources = $this->_indexSourcesByKey($baseSources);

			foreach ($settings['sources'] as $key => $source)
			{
				if (!isset($indexedBaseSources[$key]))
				{
					unset($settings['sources']);
				}
			}
		}

		$affectedRows = craft()->db->createCommand()->insertOrUpdate('elementindexsettings',
			array('type' => $elementTypeClass),
			array('settings' => JsonHelper::encode($settings))
		);

		if ($affectedRows)
		{
			$this->_indexSettings[$elementTypeClass] = $settings;
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns the element index sources in the custom groupings/order.
	 *
	 * @param string $elementTypeClass The element type class
	 * @param string $context          The context
	 *
	 * @return array
	 */
	public function getSources($elementTypeClass, $context = 'index')
	{
		$settings = $this->getSettings($elementTypeClass);
		$elementType = craft()->elements->getElementType($elementTypeClass);
		$baseSources = $this->_normalizeSources($elementType->getSources($context));
		$sources = array();

		// Should we output the sources in a custom order?
		if (isset($settings['sourceOrder']))
		{
			// Index the sources by their keys
			$indexedBaseSources = $this->_indexSourcesByKey($baseSources);

			// Assemble the customized source list
			$pendingHeading = null;

			foreach ($settings['sourceOrder'] as $source)
			{
				list($type, $value) = $source;

				if ($type == 'heading')
				{
					// Queue it up. We'll only add it if a real source follows
					$pendingHeading = $value;
				}
				else if (isset($indexedBaseSources[$value]))
				{
					// If there's a pending heading, add that first
					if ($pendingHeading !== null)
					{
						$sources[] = array('heading' => $pendingHeading);
						$pendingHeading = null;
					}

					$sources[] = $indexedBaseSources[$value];

					// Unset this so we can have a record of unused sources afterward
					unset($indexedBaseSources[$value]);
				}
			}

			// Append any remaining sources to the end of the list
			if ($indexedBaseSources)
			{
				$sources[] = array('heading' => '');

				foreach ($indexedBaseSources as $source)
				{
					$sources[] = $source;
				}
			}
		}
		else
		{
			$sources = $baseSources;
		}

		return $sources;
	}

	/**
	 * Returns all of the available attributes that can be shown for a given element type source.
	 *
	 * @param string $elementTypeClass The element type class name
	 * @param bool   $includeFields    Whether custom fields should be included in the list
	 *
	 * @return array
	 */
	public function getAvailableTableAttributes($elementTypeClass, $includeFields = true)
	{
		$elementType = craft()->elements->getElementType($elementTypeClass);
		$attributes = $elementType->defineAvailableTableAttributes();

		foreach ($attributes as $key => $info)
		{
			if (!is_array($info))
			{
				$attributes[$key] = array('label' => $info);
			}
			else if (!isset($info['label']))
			{
				$attributes[$key]['label'] = '';
			}
		}

		if ($includeFields)
		{
			// Mix in custom fields
			foreach ($this->getAvailableTableFields($elementTypeClass) as $field)
			{
				$attributes['field:'.$field->id] = array('label' => $field->name);
			}
		}

		return $attributes;
	}

	/**
	 * Returns the attributes that should be shown for a given element type source.
	 *
	 * @param string $elementTypeClass The element type class name
	 * @param string $sourceKey        The element type source key
	 *
	 * @return array
	 */
	public function getTableAttributes($elementTypeClass, $sourceKey)
	{
		$settings = $this->getSettings($elementTypeClass);
		$availableAttributes = $this->getAvailableTableAttributes($elementTypeClass);
		$attributes = array();

		// Start with the first available attribute, no matter what
		$firstKey = null;

		foreach ($availableAttributes as $key => $attributeInfo)
		{
			$firstKey = $key;
			$attributes[] = array($key, $attributeInfo);
			break;
		}

		// Is there a custom attributes list?
		if (isset($settings['sources'][$sourceKey]['tableAttributes']))
		{
			$attributeKeys = $settings['sources'][$sourceKey]['tableAttributes'];
		}
		else
		{
			$elementType = craft()->elements->getElementType($elementTypeClass);
			$attributeKeys = $elementType->getDefaultTableAttributes($sourceKey);
		}

		// Assemble the remainder of the list
		foreach ($attributeKeys as $key)
		{
			if ($key != $firstKey && isset($availableAttributes[$key]))
			{
				$attributes[] = array($key, $availableAttributes[$key]);
			}
		}

		return $attributes;
	}

	/**
	 * Returns the fields that are available to be shown as table attributes.
	 *
	 * @param string $elementTypeClass The element type class name
	 *
	 * @return FieldModel[]
	 */
	public function getAvailableTableFields($elementTypeClass)
	{
		$fields = craft()->fields->getFieldsByElementType($elementTypeClass);
		$availableFields = array();

		foreach ($fields as $field)
		{
			$fieldType = $field->getFieldType();

			if ($fieldType && $fieldType instanceof IPreviewableFieldType)
			{
				$availableFields[] = $field;
			}
		}

		return $availableFields;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Normalizes an element type’s source list.
	 *
	 * @param array $sources
	 *
	 * @return array
	 */
	private function _normalizeSources($sources)
	{
		if (!is_array($sources))
		{
			return array();
		}

		$normalizedSources = array();
		$pendingHeading = null;

		foreach ($sources as $key => $source)
		{
			// Is this a heading?
			if (array_key_exists('heading', $source))
			{
				$pendingHeading = $source['heading'];
			}
			else
			{
				// Is there a pending heading?
				if ($pendingHeading !== null)
				{
					$normalizedSources[] = array('heading' => $pendingHeading);
					$pendingHeading = null;
				}

				// Ensure the key is specified in the source
				if (!is_numeric($key))
				{
					$source['key'] = $key;
				}

				// Only allow sources that have a key
				if (empty($source['key']))
				{
					continue;
				}

				$normalizedSources[] = $source;
			}
		}

		return $normalizedSources;
	}

	/**
	 * Indexes a list of sources by their key.
	 *
	 * @param array $sources
	 *
	 * @return array
	 */
	private function _indexSourcesByKey($sources)
	{
		$indexedSources = array();

		foreach ($sources as $source)
		{
			if (isset($source['key']))
			{
				$indexedSources[$source['key']] = $source;
			}
		}

		return $indexedSources;
	}
}
