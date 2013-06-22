<?php
namespace Craft;

/**
 * Stores the data for Rich Text fields.
 */
 class RichTextData extends \Twig_Markup
 {
 	private $_pages;

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
