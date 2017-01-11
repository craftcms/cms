<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\fields\data;

use Craft;

/**
 * Stores the data for Rich Text fields.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class RichTextData extends \Twig_Markup
{
    // Properties
    // =========================================================================

    /**
     * @var
     */
    private $_pages;

    /**
     * @var string|null
     */
    private $_rawContent;

    // Public Methods
    // =========================================================================

    /**
     * Constructor
     *
     * @param string $content
     */
    public function __construct(string $content)
    {
        // Save the raw content in case we need it later
        $this->_rawContent = $content;

        // Parse the ref tags
        $content = Craft::$app->getElements()->parseRefs($content);

        parent::__construct($content, Craft::$app->charset);
    }

    /**
     * Returns the raw content, with reference tags still in-tact.
     *
     * @return string
     */
    public function getRawContent(): string
    {
        return $this->_rawContent;
    }

    /**
     * Returns the parsed content, with reference tags returned as HTML links.
     *
     * @return string
     */
    public function getParsedContent(): string
    {
        return (string)$this;
    }

    /**
     * Returns an array of the individual page contents.
     *
     * @return \Twig_Markup[]
     */
    public function getPages(): array
    {
        if ($this->_pages !== null) {
            return $this->_pages;
        }

        $this->_pages = [];
        $pages = explode('<!--pagebreak-->', (string)$this);

        foreach ($pages as $page) {
            $this->_pages[] = new \Twig_Markup($page, Craft::$app->charset);
        }

        return $this->_pages;
    }

    /**
     * Returns a specific page.
     *
     * @param int $pageNumber
     *
     * @return string|null
     */
    public function getPage(int $pageNumber)
    {
        $pages = $this->getPages();

        if (isset($pages[$pageNumber - 1])) {
            return $pages[$pageNumber - 1];
        }

        return null;
    }

    /**
     * Returns the total number of pages.
     *
     * @return int
     */
    public function getTotalPages(): int
    {
        return count($this->getPages());
    }
}
