<?php

namespace Craft;

/**
 * Class CraftCacheStrategy
 */
class CraftCacheStrategy
{

	private $_id = null;
	private $_uri = null;
	private $_unHashedKey = null;
	private $_elementDependencies = array();

	/**
	 * @param $params
	 * @return bool|string
	 * @throws Exception
	 */
	public function generateKey($params)
	{
		$key = '';

		// Make sure it's something we can cache.
		if (
			!isset($params['entry']) &&
			!isset($params['entries']) &&
			!isset($params['section']) &&
			!isset($params['user']) &&
			!isset($params['id']))
		{
			throw new Exception(Craft::t('You must specify at least one of the following properties to cache: entry, entries, section, user or id.'));
		}

		if (
			// Disable caching if 'freshAdmin' is set and the currently logged in user is an admin.
			(isset($params['freshAdmin']) && $params['freshAdmin'] === true && craft()->userSession->isAdmin()) ||

			// Disable caching if 'freshUser' is set and there is a currently logged in user.
			(isset($params['freshUser']) && $params['freshUser'] === true && craft()->userSession->isLoggedIn()) ||

			// Disable caching if 'fresh' is set.
			(isset($params['fresh']) && $params['fresh'] === true)
		)
		{
			return false;
		}

		//
		if (isset($params['entry']))
		{
			if (!is_object($params['entry']) || get_class($params['entry']) !== 'Craft\\EntryModel')
			{
				throw new Exception(Craft::t('The supplied entry is must be an EntryModel.'));
			}

			$this->_elementDependencies[] = $params['entry'];

			$key .= $this->hashElement($params['entry']);
		}

		if (isset($params['entries']))
		{
			if (!is_array($params['entries']))
			{
				throw new Exception(Craft::t('Expecting entries to be an array of EntryModel objects, given: '.get_class($params['entries'])));
			}

			foreach ($params['entries'] as $entry)
			{
				if (!is_object($entry) || get_class($entry) !== 'Craft\\EntryModel')
				{
					throw new Exception(Craft::t('Elements of the array passed to entries must be EntryModel objects.'));
				}

				$this->_elementDependencies[] = $entry;

				$key .= $this->hashElement($entry);
			}
		}

		if (isset($params['user']))
		{
			if (!is_object($params['user']) || get_class($params['user']) !== 'Craft\\UserModel')
			{
				throw new Exception(Craft::t('The supplied user is invalid, must be a UserModel.'));
			}

			$this->_elementDependencies[] = $params['user'];

			$key .= $this->hashElement($params['user']);
		}

		if (isset($params['id']))
		{
			$this->_id = $params['id'];
			$key .= "{$params['id']}";
		}

		if (isset($params['global']) && !$params['global'])
		{
			$requestUri = craft()->request->getRequestUri();
			$key .= $requestUri;
			$this->_uri = $requestUri;
		}

		$this->_unHashedKey = $key;

		return sha1($key);
	}

	/**
	 * @param $element
	 * @return string
	 */
	public function hashElement($element)
	{
		$id = $element->id;
		$updated = $element->dateUpdated->format('U');
		return $id.$updated.get_class($element);
	}

	/**
	 * @param $hashedKey
	 * @param $html
	 */
	public function saveBlock($hashedKey, $html)
	{
		$cacheValue = new CacheValueModel();
		$cacheValue->category = CacheCategory::Template;
		$cacheValue->unHashedKey = $this->_unHashedKey;
		$cacheValue->value = $html;

		if ($this->_uri)
		{
			$cacheValue->options = array('uri' => $this->_uri);
		}

		if (count($this->_elementDependencies) > 0)
		{
			craft()->cache->set($hashedKey,$cacheValue, 0, new ElementsDependency($this->_elementDependencies));
		}
		else
		{
			craft()->cache->set($hashedKey, $cacheValue, 0);
		}
	}

	/**
	 * @param $hashedKey
	 * @return bool|mixed
	 */
	public function fetchBlock($hashedKey)
	{
		if ($hashedKey === false)
		{
			return false;
		}

		$cacheValueModel = craft()->cache->get($hashedKey);

		if ($cacheValueModel)
		{
			return $cacheValueModel->value;
		}
	}
}
