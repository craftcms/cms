<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\elements\db\EagerLoadInfo;
use craft\web\twig\AllowedInSandbox;
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
     * @var ElementInterface[]|null All elements that the element was queried with.
     * @since 5.0.0
     */
    public ?array $elementQueryResult = null;

    /**
     * @var EagerLoadInfo|null Info about the eager loading setup used to query this element.
     * @since 5.0.0
     */
    public ?EagerLoadInfo $eagerLoadInfo = null;

    /**
     * @var int|null The element’s ID
     */
    #[AllowedInSandbox]
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
     * @var bool Whether provisional changes have been loaded onto this element.
     * @since 5.9.0
     */
    public bool $hasProvisionalChanges = false;

    /**
     * @var string|null The element’s UID
     */
    #[AllowedInSandbox]
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
     * @var bool Whether the element is enabled
     */
    #[AllowedInSandbox]
    public bool $enabled = true;

    /**
     * @var bool Whether the element is archived
     */
    #[AllowedInSandbox]
    public bool $archived = false;

    /**
     * @var int|null The site ID the element is associated with
     */
    #[AllowedInSandbox]
    public ?int $siteId = null;

    /**
     * @var string|null The element’s title
     */
    #[AllowedInSandbox]
    public ?string $title = null;

    /**
     * @var string|null The element’s slug
     */
    #[AllowedInSandbox]
    public ?string $slug = null;

    /**
     * @var string|null The element’s URI
     */
    #[AllowedInSandbox]
    public ?string $uri = null;

    /**
     * @var DateTime|null The date that the element was created
     */
    #[AllowedInSandbox]
    public ?DateTime $dateCreated = null;

    /**
     * @var DateTime|null The date that the element was last updated
     */
    #[AllowedInSandbox]
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
    #[AllowedInSandbox]
    public ?DateTime $dateDeleted = null;

    /**
     * @var bool|null Whether the element was deleted along with its owner
     * @since 5.0.0
     */
    public ?bool $deletedWithOwner = null;

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
    #[AllowedInSandbox]
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
     * @var ElementInterface|null The element that this element is being propagated from.
     * @since 5.0.0
     */
    public ?ElementInterface $propagatingFrom = null;

    /**
     * @var bool Whether all element attributes should be propagated across all its supported sites, even if that means
     * overwriting existing site-specific values.
     * @since 3.2.0
     */
    public bool $propagateAll = false;

    /**
     * @var bool Whether all required element attributes should be propagated across all its supported sites, but only if otherwise
     * they wouldn’t validate.
     * @since 5.9.0
     */
    public bool $propagateRequired = false;

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
     * @var bool Whether this is for a newly-created site.
     * @since 5.6.10
     */
    public bool $isNewSite = false;

    /**
     * @var bool Whether the element is being resaved by a ResaveElement job or a `resave` console command.
     * @since 3.1.22
     */
    public bool $resaving = false;

    /**
     * @var ElementInterface|null The element that this element is duplicating.
     */
    public ?ElementInterface $duplicateOf = null;

    /**
     * @var bool Whether the element is being saved for the first time in a normal state (not as a draft or revision).
     * @since 3.7.5
     */
    public bool $firstSave = false;

    /**
     * @var bool Whether the element is a draft that is about to be applied to the canonical element.
     * @since 5.9.0
     */
    public bool $applyingDraft = false;

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
     * @var string|null The view mode used to show this element (e.g. `structure`, `table`, `thumbs`, `cards`).
     * @since 5.6.0
     */
    public ?string $viewMode = null;

    /**
     * @var bool Whether the element should definitely be saved, if it’s a nested element being considered
     * for saving by [[NestedElementManager]].
     * @since 5.0.0
     */
    public bool $forceSave = false;

    /**
     * @var bool Whether the element is being hard-deleted.
     * @since 3.2.0
     */
    public bool $hardDelete = false;

    /**
     * @var bool Whether the element’s search keywords should be indexed immediately.
     *
     * If `null`, the search index will only be updated immediately for console requests.
     *
     * @since 5.8.0
     */
    public ?bool $updateSearchIndexImmediately = null;
}
