<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\base;

use DateTime;

/**
 * ElementTrait implements the common methods and properties for element classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
trait ElementTrait
{
    // Properties
    // =========================================================================

    /**
     * @var int The element’s ID
     */
    public $id;

    /**
     * @var string The element’s UID
     */
    public $uid;

    /**
     * @var int The element’s content row ID
     */
    public $contentId;

    /**
     * @var boolean Whether the element is enabled
     */
    public $enabled = true;

    /**
     * @var boolean Whether the element is archived
     */
    public $archived = false;

    /**
     * @var integer The site ID the element is associated with
     */
    public $siteId;

    /**
     * @var boolean Whether the element is enabled for this site.
     */
    public $enabledForSite = true;

    /**
     * @var string The element’s title
     */
    public $title;

    /**
     * @var string The element’s slug
     */
    public $slug;

    /**
     * @var string The element’s URI
     */
    public $uri;

    /**
     * @var DateTime The date that the element was created
     */
    public $dateCreated;

    /**
     * @var DateTime The date that the element was last updated
     */
    public $dateUpdated;

    /**
     * @var int The element’s structure’s root ID
     */
    public $root;

    /**
     * @var int The element’s left position within its structure
     */
    public $lft;

    /**
     * @var int The element’s right position within its structure
     */
    public $rgt;

    /**
     * @var int The element’s level within its structure
     */
    public $level;

    /**
     * @var int The element’s search score, if the [[\craft\app\elements\db\ElementQuery::search]] parameter was used when querying for the element
     */
    public $searchScore;

    /**
     * @var boolean Whether the element is still awaiting its custom field values
     */
    public $awaitingFieldValues = false;
}
