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
 * @since 3.0.0
 */
trait ElementTrait
{
    /**
     * @var int|null The element’s ID
     */
    public ?int $id = null;

    /**
     * @var string|null The element’s temporary ID (only used if the element’s URI format contains {id})
     */
    public ?string $tempId = null;

    /**
     * @var int|null The ID of the draft’s row in the `drafts` table
     * @since 3.2.0
     */
    public ?int $draftId = null;

    /**
     * @var int|null The ID of the revision’s row in the `revisions` table
     * @since 3.2.0
     */
    public ?int $revisionId = null;

    /**
     * @var bool Whether this is a provisional draft.
     * @since 3.7.0
     */
    public bool $isProvisionalDraft = false;

    /**
     * @var string|null The element’s UID
     */
    public ?string $uid = null;

    /**
     * @var int|null The ID of the element’s record in the `elements_sites` table
     * @since 3.5.2
     */
    public ?int $siteSettingsId = null;

    /**
     * @var int|null The element’s field layout ID
     */
    public ?int $fieldLayoutId = null;

    /**
     * @var int|null The element’s structure ID
     */
    public ?int $structureId = null;

    /**
     * @var int|null The element’s content row ID
     */
    public ?int $contentId = null;

    /**
     * @var bool Whether the element is enabled
     */
    public bool $enabled = true;

    /**
     * @var bool Whether the element is archived
     */
    public bool $archived = false;

    /**
     * @var int|null The site ID the element is associated with
     */
    public ?int $siteId = null;

    /**
     * @var string|null The element’s title
     */
    public ?string $title = null;

    /**
     * @var string|null The element’s slug
     */
    public ?string $slug = null;

    /**
     * @var string|null The element’s URI
     */
    public ?string $uri = null;

    /**
     * @var DateTime|null The date that the element was created
     */
    public ?DateTime $dateCreated = null;

    /**
     * @var DateTime|null The date that the element was last updated
     */
    public ?DateTime $dateUpdated = null;

    /**
     * @var DateTime|null The date that the canonical element was last merged into this one
     * @since 3.7.0
     */
    public ?DateTime $dateLastMerged = null;

    /**
     * @var DateTime|null The date that the element was trashed
     * @since 3.2.0
     */
    public ?DateTime $dateDeleted = null;

    /**
     * @var int|null The element’s structure’s root ID
     */
    public ?int $root = null;

    /**
     * @var int|null The element’s left position within its structure
     */
    public ?int $lft = null;

    /**
     * @var int|null The element’s right position within its structure
     */
    public ?int $rgt = null;

    /**
     * @var int|null The element’s level within its structure
     */
    public ?int $level = null;

    /**
     * @var int|null The element’s search score, if the [[\craft\elements\db\ElementQuery::search]] parameter was used when querying for the element
     */
    public ?int $searchScore = null;

    /**
     * @var bool Whether the element has been soft-deleted.
     */
    public bool $trashed = false;

    /**
     * @var bool Whether the element is still awaiting its custom field values
     */
    public bool $awaitingFieldValues = false;

    /**
     * @var bool Whether the element is being saved in the context of propagating another site's version of the element.
     */
    public bool $propagating = false;

    /**
     * @var bool Whether all element attributes should be propagated across all its supported sites, even if that means
     * overwriting existing site-specific values.
     * @since 3.2.0
     */
    public bool $propagateAll = false;

    /**
     * @var int[] The site IDs that the element was just propagated to for the first time.
     * @since 3.2.9
     */
    public array $newSiteIds = [];

    /**
     * @var bool Whether the element is being saved to the current site for the first time.
     * @since 3.7.15
     */
    public bool $isNewForSite = false;

    /**
     * @var bool Whether the element is being resaved by a ResaveElement job or a `resave` console command.
     * @since 3.1.22
     */
    public bool $resaving = false;

    /**
     * @var ElementInterface|null The element that this element is being duplicated by.
     */
    public ?ElementInterface $duplicateOf = null;

    /**
     * @var bool Whether the element is being saved for the first time in a normal state (not as a draft or revision).
     * @since 3.7.5
     */
    public bool $firstSave = false;

    /**
     * @var bool Whether recent changes to the canonical element are being merged into this element.
     * @since 3.7.0
     */
    public bool $mergingCanonicalChanges = false;

    /**
     * @var bool Whether the element is being updated from a derivative element, such as a draft or revision.
     *
     * If this is true, the derivative element can be accessed via [[duplicateOf]].
     *
     * @since 3.7.0
     */
    public bool $updatingFromDerivative = false;

    /**
     * @var bool Whether the element is currently being previewed.
     * @since 3.2.0
     */
    public bool $previewing = false;

    /**
     * @var bool Whether the element is being hard-deleted.
     * @since 3.2.0
     */
    public bool $hardDelete = false;
}
