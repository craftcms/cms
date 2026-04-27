<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\NestedElementQueryInterface;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use DateTime;

/**
 * HasCanonical provides support for canonical elements and their derivatives.
 *
 * This trait contains methods for tracking and managing the relationship between
 * canonical (source) elements and their derivatives such as drafts and revisions,
 * including merging upstream changes from the canonical element into derivatives.
 *
 * @property int|null $canonicalId The element's canonical ID
 * @property-read string $canonicalUid The element's canonical UID
 * @property-read bool $isCanonical Whether this is the canonical element
 * @property-read bool $isDerivative Whether this is a derivative element, such as a draft or revision
 * @property ElementInterface|null $canonical The canonical element, if one exists for the current site
 *
 * @internal
 */
trait HasCanonical
{
    /**
     * @var DateTime|null The date that the canonical element was last merged into this one
     */
    public ?DateTime $dateLastMerged = null;

    /**
     * @var bool Whether recent changes to the canonical element are being merged into this element.
     */
    public bool $mergingCanonicalChanges = false;

    /**
     * @var bool Whether the element is being updated from a derivative element, such as a draft or revision.
     *
     * If this is true, the derivative element can be accessed via [[duplicateOf]].
     */
    public bool $updatingFromDerivative = false;

    /**
     * @see getCanonicalId()
     * @see setCanonicalId()
     * @see getIsCanonical()
     * @see getIsDerivative()
     */
    private ?int $_canonicalId = null;

    /**
     * @see getCanonical()
     */
    private ElementInterface|false|null $_canonical = null;

    /**
     * @see getCanonical()
     */
    private ElementInterface|false|null $_canonicalAnySite = null;

    /**
     * @see getCanonicalUid()
     */
    private ?string $_canonicalUid = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIsCanonical(): bool
    {
        return ! isset($this->_canonicalId);
    }

    public function getIsDerivative(): bool
    {
        return ! $this->getIsCanonical();
    }

    public function getCanonical(bool $anySite = false): ElementInterface
    {
        if ($this->getIsCanonical()) {
            return $this;
        }

        $prop = $anySite ? '_canonicalAnySite' : '_canonical';

        if (! isset($this->$prop)) {
            $query = static::find()
                ->id($this->_canonicalId)
                ->siteId($anySite ? '*' : $this->siteId)
                ->preferSites([$this->siteId])
                ->structureId($this->structureId)
                ->unique()
                ->status(null)
                ->trashed(null)
                ->ignorePlaceholders();

            if ($this instanceof NestedElementInterface && $query instanceof NestedElementQueryInterface) {
                $query->fieldId($this->getField()?->id);
            }

            $this->$prop = $query->one();
        }

        return $this->$prop ?: $this;
    }

    public function setCanonical(ElementInterface $element): void
    {
        if ($this->getIsCanonical()) {
            throw new NotSupportedException('setCanonical() can only be called on a derivative element.');
        }

        $this->_canonical = $element;
    }

    public function getCanonicalId(): ?int
    {
        return $this->_canonicalId ?? $this->id;
    }

    public function setCanonicalId(?int $canonicalId): void
    {
        $this->_canonicalId = $canonicalId !== $this->id ? $canonicalId : null;
        $this->_canonical = null;
    }

    public function getCanonicalUid(): ?string
    {
        if ($this->getIsCanonical()) {
            return $this->uid;
        }

        if ($this->_canonical) {
            return $this->_canonical->uid;
        }

        if (! isset($this->_canonicalUid)) {
            $this->_canonicalUid = static::find()
                ->id($this->_canonicalId)
                ->site('*')
                ->status(null)
                ->ignorePlaceholders()
                ->select(['elements.uid'])
                ->one()?->uid;
        }

        return $this->_canonicalUid;
    }

    public function getIsUnpublishedDraft(): bool
    {
        return $this->getIsDraft() && $this->getIsCanonical();
    }

    public function mergeCanonicalChanges(): void
    {
        if (($canonical = $this->getCanonical()) === $this) {
            return;
        }

        // Update any attributes that were modified upstream
        foreach ($this->getOutdatedAttributes() as $attribute) {
            if (! $this->isAttributeModified($attribute)) {
                $this->$attribute = $canonical->$attribute;
            }
        }

        foreach ($this->getOutdatedFields() as $fieldHandle) {
            if (
                ! $this->isFieldModified($fieldHandle) &&
                ($field = $this->fieldByHandle($fieldHandle)) !== null
            ) {
                $field->copyValue($canonical, $this);
            }
        }
    }
}
