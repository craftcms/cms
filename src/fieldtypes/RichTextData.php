<?php
namespace Craft;

/**
 * Stores the data for Rich Text fields.
 */
class RichTextData extends \Twig_Markup
{
	private $_pages;
	private $_rawContent;

	/**
	 * Constructor
	 */
	public function __construct($content, $charset)
	{
		// Save the raw content in case we need it later
		$this->_rawContent = $content;

		// Parse the ref tags
		$content = craft()->elements->parseRefs($content);
		parent::__construct($content, $charset);
	}

	/**
	 * Returns the raw content, with reference tags still in-tact.
	 *
	 * @return string
	 */
	public function getRawContent()
	{
		return $this->_rawContent;
	}

	/**
	 * Returns an array of the individual page contents.
	 *
	 * @return array
	 */
	public function getPages()
	{
		if (!isset($this->_pages))
		{
			$this->_pages = array();
			$pages = explode('<!--pagebreak-->', $this->content);

			foreach ($pages as $page)
			{
				$this->_pages[] = new \Twig_Markup($page, $this->charset);
			}
		}

		return $this->_pages;
	}

	/**
	 * Returns a specific page.
	 *
	 * @param int $pageNumber
	 * @return string|null
	 */
	public function getPage($pageNumber)
	{
		$pages = $this->getPages();

		if (isset($pages[$pageNumber - 1]))
		{
			return $pages[$pageNumber - 1];
		}
	}

	/**
	 * Returns the total number of pages.
	 *
	 * @return int
	 */
	public function getTotalPages()
	{
		return count($this->getPages());
	}
}
