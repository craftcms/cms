<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Enums\AttributeStatus;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

use function CraftCms\Cms\t;

/**
 * Provides change tracking functionality for elements.
 *
 * This trait handles methods related to tracking modified and outdated attributes,
 * dirty state management, and translatability of core attributes like title and slug.
 *
 * @internal
 */
trait TracksChanges
{
    /**
     * @var array<string,int>|null
     *
     * @see validate()
     */
    private ?array $_attributeNames = null;

    /**
     * @see _outdatedAttributes()
     */
    private ?array $_outdatedAttributes = null;

    /**
     * @see _modifiedAttributes()
     */
    private ?array $_modifiedAttributes = null;

    /**
     * @var bool Whether all attributes and field values should be considered dirty.
     *
     * @see getDirtyAttributes()
     * @see getDirtyFields()
     * @see isFieldDirty()
     */
    private bool $_allDirty = false;

    /**
     * @var array<string, int|string|bool> Record of dirty attributes.
     *
     * @see getDirtyAttributes()
     * @see isAttributeDirty()
     */
    private array $_dirtyAttributes = [];

    /**
     * @var string|null The initial title value, if there was one.
     *
     * @see getDirtyAttributes()
     */
    private ?string $_savedTitle = null;

    /**
     * @see getIsFresh()
     * @see setIsFresh()
     */
    private ?bool $_isFresh = null;

    /**
     * {@inheritdoc}
     */
    public static function trackChanges(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeStatus(string $attribute): ?array
    {
        if ($this->isAttributeModified($attribute)) {
            return [
                AttributeStatus::Modified,
                t('This field has been modified.'),
            ];
        }

        if ($this->isAttributeOutdated($attribute)) {
            return [
                AttributeStatus::Outdated,
                t('This field was updated in the Current revision.'),
            ];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getOutdatedAttributes(): array
    {
        return array_keys($this->_outdatedAttributes());
    }

    /**
     * {@inheritdoc}
     */
    public function isAttributeOutdated(string $name): bool
    {
        return isset($this->_outdatedAttributes()[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getModifiedAttributes(): array
    {
        return array_keys($this->_modifiedAttributes());
    }

    /**
     * {@inheritdoc}
     */
    public function isAttributeModified(string $name): bool
    {
        return isset($this->_modifiedAttributes()[$name]);
    }

    /**
     * @return array The attribute names that have been modified for this element
     */
    private function _outdatedAttributes(): array
    {
        if (! static::trackChanges() || $this->getIsCanonical() || $this->getIsRevision()) {
            return [];
        }

        return $this->_outdatedAttributes ??= DB::table(Table::CHANGEDATTRIBUTES)
            ->where('elementId', $this->id)
            ->where('siteId', $this->siteId)
            ->when(
                value: $this->dateLastMerged,
                callback: fn (Builder $query) => $query->where('dateUpdated', '>=', $this->dateLastMerged),
                default: fn (Builder $query) => $query->where('dateUpdated', '>=', $this->dateCreated),
            )
            ->pluck('attribute')
            ->flip()
            ->all();
    }

    /**
     * @return array The attribute names that have been modified for this element
     */
    private function _modifiedAttributes(): array
    {
        if (! static::trackChanges() || $this->getIsCanonical()) {
            return [];
        }

        return $this->_modifiedAttributes ??= DB::table(Table::CHANGEDATTRIBUTES)
            ->where('elementId', $this->id)
            ->where('siteId', $this->siteId)
            ->pluck('attribute')
            ->flip()
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function isAttributeDirty(string $name): bool
    {
        if ($this->_allDirty()) {
            return true;
        }

        return isset($this->_dirtyAttributes[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDirtyAttributes(): array
    {
        if (static::hasTitles() && $this->title !== $this->_savedTitle) {
            $this->_dirtyAttributes['title'] = true;
        }

        return array_keys($this->_dirtyAttributes);
    }

    /**
     * {@inheritdoc}
     */
    public function setDirtyAttributes(array $names, bool $merge = true): void
    {
        $attributes = array_flip($names);

        $this->_dirtyAttributes = $merge
            ? array_merge($this->_dirtyAttributes, $attributes)
            : $attributes;
    }

    /**
     * Returns whether all fields and attributes should be considered dirty.
     */
    private function _allDirty(): bool
    {
        return $this->_allDirty || $this->resaving;
    }

    /**
     * {@inheritdoc}
     */
    public function markAsDirty(): void
    {
        $this->_allDirty = true;
    }

    /**
     * {@inheritdoc}
     */
    public function markAsClean(): void
    {
        $this->_allDirty = false;
        $this->_dirtyAttributes = [];
        $this->setDirtyFields([], false);

        if (static::hasTitles()) {
            $this->_savedTitle = $this->title;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIsFresh(): bool
    {
        if ($this->errors()->isNotEmpty()) {
            return false;
        }

        return ! isset($this->siteSettingsId) || ($this->_isFresh ?? false);
    }

    /**
     * {@inheritdoc}
     */
    public function setIsFresh(bool $isFresh = true): void
    {
        $this->_isFresh = $isFresh;
    }
}
