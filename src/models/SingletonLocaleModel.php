<?php
namespace Blocks;

/**
 * Singleton locale model class
 */
class SingletonLocaleModel extends BaseModel
{
	private $_uri;

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'id'          => AttributeType::Number,
			'singletonId' => AttributeType::Number,
			'locale'      => AttributeType::Locale,
		);
	}

	/**
	 * Returns the locale's URI.
	 *
	 * @return string
	 */
	public function getUri()
	{
		if (!isset($this->_uri))
		{
			if ($this->id)
			{
				$this->_uri = blx()->elements->getElementUriForLocale($this->singletonId, $this->locale);
			}
			else
			{
				$this->_uri = '';
			}
		}

		return $this->_uri;
	}

	/**
	 * Sets the locale's URI.
	 *
	 * @param string $uri
	 */
	public function setUri($uri)
	{
		$this->_uri = $uri;
	}
}
