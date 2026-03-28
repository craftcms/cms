<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use craft\base\ElementInterface;
use craft\base\NestedElementInterface;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use yii\base\InvalidConfigException;

/**
 * Localizable provides localization functionality for elements.
 *
 * This trait contains methods for determining site-specific properties and querying localized versions of elements.
 *
 * @property int[]|array $supportedSites The sites this element is associated with
 * @property Site $site Site the element is associated with
 *
 * @internal
 */
trait Localizable
{
    /**
     * @see getIsCrossSiteCopyable()
     */
    private bool $isCrossSiteCopyable;

    /**
     * @var int|null The site ID the element is associated with
     */
    #[AllowedInSandbox]
    public ?int $siteId = null;

    /**
     * @var bool Whether the element is being saved in the context of propagating another site's version of the element.
     */
    public bool $propagating = false;

    /**
     * @var ElementInterface|null The element that this element is being propagated from.
     *
     * @since 5.0.0
     */
    public ?ElementInterface $propagatingFrom = null;

    /**
     * @var bool Whether all element attributes should be propagated across all its supported sites, even if that means
     *           overwriting existing site-specific values.
     *
     * @since 3.2.0
     */
    public bool $propagateAll = false;

    /**
     * @var bool Whether all required element attributes should be propagated across all its supported sites, but only if otherwise
     *           they wouldn’t validate.
     *
     * @since 5.9.0
     */
    public bool $propagateRequired = false;

    /**
     * @var int[] The site IDs that the element was just propagated to for the first time.
     *
     * @since 3.2.9
     */
    public array $newSiteIds = [];

    /**
     * @var bool Whether the element is being saved to the current site for the first time.
     *
     * @since 3.7.15
     */
    public bool $isNewForSite = false;

    /**
     * @var bool Whether this is for a newly-created site.
     *
     * @since 5.6.10
     */
    public bool $isNewSite = false;

    public static function isLocalized(): bool
    {
        return false;
    }

    public function getRootOwner(): ElementInterface
    {
        if (! $this instanceof NestedElementInterface) {
            return $this;
        }

        return ($owner = $this->getOwner())
            ? $owner->getRootOwner()
            : $this;
    }

    /**
     * @since 3.5.0
     */
    public function getLocalized(): ElementQueryInterface|ElementQuery|ElementCollection
    {
        // Eager-loaded?
        if ($localized = $this->getEagerLoadedElements('localized')) {
            return $localized;
        }

        return static::find()
            ->id($this->id ?: false)
            ->structureId($this->structureId)
            ->siteId(['not', $this->siteId])
            ->drafts(null)
            // The provisionalDraft state could have just changed (e.g. `elements/save-draft`)
            // so don't filter based on one or the other
            ->provisionalDrafts(null)
            ->revisions(null);
    }

    public function getIsCrossSiteCopyable(): bool
    {
        if (isset($this->isCrossSiteCopyable)) {
            return $this->isCrossSiteCopyable;
        }

        if (! Sites::isMultiSite()) {
            return $this->isCrossSiteCopyable = false;
        }

        if (count(ElementHelper::editableSiteIdsForElement($this)) <= 1) {
            return $this->isCrossSiteCopyable = false;
        }

        // Check if the element exists in other sites
        $otherSiteStatuses = ElementHelper::siteStatusesForElement($this, true);
        $otherSiteIds = array_keys($otherSiteStatuses);
        $existsInOtherSites = ! empty(array_diff($otherSiteIds, [$this->siteId]));

        return $this->isCrossSiteCopyable = $existsInOtherSites;
    }

    /**
     * @throws InvalidConfigException if [[siteId]] is invalid
     */
    public function getSite(): Site
    {
        if (isset($this->siteId) && $site = Sites::getSiteById($this->siteId, true)) {
            return $site;
        }

        throw new InvalidConfigException("Invalid site ID: {$this->siteId}");
    }

    /**
     * @since 3.5.0
     */
    public function getLanguage(): string
    {
        return $this->getSite()->getLanguage();
    }

    public function getSupportedSites(): array
    {
        if (static::isLocalized()) {
            return Sites::getAllSiteIds()->all();
        }

        return [Sites::getPrimarySite()->id];
    }

    public function getIsTitleTranslatable(): bool
    {
        return true;
    }

    public function getTitleTranslationDescription(): ?string
    {
        return TranslationMethod::Site->description();
    }

    public function getTitleTranslationKey(): string
    {
        return TranslationMethod::Site->elementKey($this);
    }

    public function getIsSlugTranslatable(): bool
    {
        return true;
    }

    public function getSlugTranslationDescription(): ?string
    {
        return TranslationMethod::Site->description();
    }

    public function getSlugTranslationKey(): string
    {
        return TranslationMethod::Site->elementKey($this);
    }
}
