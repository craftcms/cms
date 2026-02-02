<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use craft\base\ElementInterface;
use craft\base\NestedElementInterface;
use craft\elements\db\NestedElementQueryInterface;
use yii\base\NotSupportedException;

/**
 * @mixin \CraftCms\Cms\Element\Element
 */
trait HasCanonical
{
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

    /**
     * {@inheritdoc}
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getIsCanonical(): bool
    {
        return ! isset($this->_canonicalId);
    }

    /**
     * {@inheritdoc}
     */
    public function getIsDerivative(): bool
    {
        return ! $this->getIsCanonical();
    }

    /**
     * {@inheritdoc}
     */
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
                $query
                    ->fieldId($this->getField()?->id);
            }

            $this->$prop = $query->one();
        }

        return $this->$prop ?: $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setCanonical(ElementInterface $element): void
    {
        if ($this->getIsCanonical()) {
            throw new NotSupportedException('setCanonical() can only be called on a derivative element.');
        }

        $this->_canonical = $element;
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalId(): ?int
    {
        return $this->_canonicalId ?? $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setCanonicalId(?int $canonicalId): void
    {
        $this->_canonicalId = $canonicalId !== $this->id ? $canonicalId : null;
        $this->_canonical = null;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getIsUnpublishedDraft(): bool
    {
        return $this->getIsDraft() && $this->getIsCanonical();
    }

    /**
     * {@inheritdoc}
     */
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
