<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use DateTime;

/**
 * ElementTrait implements the common methods and properties for element classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
trait ElementTrait
{
    // Properties
    // =========================================================================

    /**
     * @var int|null The element’s ID
     */
    public $id;

    /**
     * @var string|null The element’s temporary ID (only used if the element's URI format contains {id})
     */
    public $tempId;

    /**
     * @var int|null The ID of the draft’s row in the `drafts` table
     */
    public $draftId;

    /**
     * @var int The ID of the revision’s row in the `revisions` table
     */
    public $revisionId;

    /**
     * @var string|null The element’s UID
     */
    public $uid;

    /**
     * @var int|null The element’s field layout ID
     */
    public $fieldLayoutId;

    /**
     * @var int|null The element’s structure ID
     */
    public $structureId;

    /**
     * @var int|null The element’s content row ID
     */
    public $contentId;

    /**
     * @var bool Whether the element is enabled
     */
    public $enabled = true;

    /**
     * @var bool Whether the element is archived
     */
    public $archived = false;

    /**
     * @var int|null The site ID the element is associated with
     */
    public $siteId;

    /**
     * @var bool Whether the element is enabled for this site.
     */
    public $enabledForSite = true;

    /**
     * @var string|null The element’s title
     */
    public $title;

    /**
     * @var string|null The element’s slug
     */
    public $slug;

    /**
     * @var string|null The element’s URI
     */
    public $uri;

    /**
     * @var DateTime|null The date that the element was created
     */
    public $dateCreated;

    /**
     * @var DateTime|null The date that the element was last updated
     */
    public $dateUpdated;

    /**
     * @var DateTime|null The date that the element was trashed
     */
    public $dateDeleted;

    /**
     * @var int|null The element’s structure’s root ID
     */
    public $root;

    /**
     * @var int|null The element’s left position within its structure
     */
    public $lft;

    /**
     * @var int|null The element’s right position within its structure
     */
    public $rgt;

    /**
     * @var int|null The element’s level within its structure
     */
    public $level;

    /**
     * @var int|null The element’s search score, if the [[\craft\elements\db\ElementQuery::search]] parameter was used when querying for the element
     */
    public $searchScore;

    /**
     * @var bool Whether the element has been soft-deleted.
     */
    public $trashed = false;

    /**
     * @var bool Whether the element is still awaiting its custom field values
     */
    public $awaitingFieldValues = false;

    /**
     * @var bool Whether the element is being saved in the context of propagating another site's version of the element.
     */
    public $propagating = false;

    /**
     * @var bool Whether all element attributes should be propagated across all its supported sites, even if that means
     * overwriting existing site-specific values.
     */
    public $propagateAll = false;

    /**
     * @var int[] The site IDs that the element was just propagated to for the first time.
     */
    public $newSiteIds = [];

    /**
     * @var bool Whether the element is being resaved by a ResaveElement job or a `resave` console command.
     */
    public $resaving = false;

    /**
     * @var ElementInterface|null The element that this element is being duplicated by.
     */
    public $duplicateOf;

    /**
     * @var bool Whether the element is currently being previewed.
     */
    public $previewing = false;

    /**
     * @var bool Whether the element is being hard-deleted.
     */
    public $hardDelete = false;
}
