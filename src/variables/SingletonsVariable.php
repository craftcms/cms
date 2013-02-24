<?php
namespace Craft;

/**
 * Singleton functions
 */
class SingletonsVariable
{
	/**
	 * Gets all singletons.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getAllSingletons($indexBy = null)
	{
		return craft()->singletons->getAllSingletons($indexBy);
	}

	/**
	 * Gets all singletons that are editable by the current user.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getEditableSingletons($indexBy = null)
	{
		return craft()->singletons->getEditableSingletons($indexBy);
	}

	/**
	 * Gets the total number of singletons.
	 *
	 * @return int
	 */
	public function getTotalSingletons()
	{
		return craft()->singletons->getTotalSingletons();
	}

	/**
	 * Gets a singleton by its ID.
	 *
	 * @param int $id
	 * @return SingletonModel|null
	 */
	public function getSingletonById($id)
	{
		return craft()->singletons->getSingletonById($id);
	}

	/**
	 * Gets a singleton by its URI.
	 *
	 * @param string $uri
	 * @return SingletonModel|null
	 */
	public function getSingletonByUri($uri)
	{
		return craft()->singletons->getSingletonByUri($uri);
	}
}
