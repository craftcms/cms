<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Enums\AttributeStatus;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

use function CraftCms\Cms\t;

/**
 * Provides change tracking functionality for elements.
 *
 * This trait contains methods related to tracking modified and outdated attributes,
 * dirty state management, and translatability of core attributes like title and slug.
 *
 * @mixin Element
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
     * @var array<string,int>|null
     *
     * @see _outdatedAttributes()
     */
    private ?array $_outdatedAttributes = null;

    /**
     * @var array<string,int>|null
     *
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

    public static function trackChanges(): bool
    {
        return false;
    }

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

    public function getOutdatedAttributes(): array
    {
        return array_keys($this->_outdatedAttributes());
    }

    public function isAttributeOutdated(string $name): bool
    {
        return isset($this->_outdatedAttributes()[$name]);
    }

    public function getModifiedAttributes(): array
    {
        return array_keys($this->_modifiedAttributes());
    }

    public function isAttributeModified(string $name): bool
    {
        return isset($this->_modifiedAttributes()[$name]);
    }

    /**
     * @return array<string,int> The attribute names that have been modified for this element
     */
    private function _outdatedAttributes(): array
    {
        if (! static::trackChanges() || $this->getIsCanonical() || $this->getIsRevision()) {
            return [];
        }

        // Outdated attributes are the ones that changed on the *canonical* element
        // since this derivative was created (or last merged).
        return $this->_outdatedAttributes ??= DB::table(Table::CHANGEDATTRIBUTES)
            ->where('elementId', $this->getCanonicalId())
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
     * @return array<string,int> The attribute names that have been modified for this element
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

    public function isAttributeDirty(string $name): bool
    {
        if ($this->_allDirty()) {
            return true;
        }

        return isset($this->_dirtyAttributes[$name]);
    }

    public function getDirtyAttributes(): array
    {
        if (static::hasTitles() && $this->title !== $this->_savedTitle) {
            $this->_dirtyAttributes['title'] = true;
        }

        return array_keys($this->_dirtyAttributes);
    }

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

    public function markAsDirty(): void
    {
        $this->_allDirty = true;
    }

    public function markAsClean(): void
    {
        $this->_allDirty = false;
        $this->_dirtyAttributes = [];
        $this->setDirtyFields([], false);

        if (static::hasTitles()) {
            $this->_savedTitle = $this->title;
        }
    }

    public function getIsFresh(): bool
    {
        if ($this->errors()->isNotEmpty()) {
            return false;
        }

        return ! isset($this->siteSettingsId) || ($this->_isFresh ?? false);
    }

    public function setIsFresh(bool $isFresh = true): void
    {
        $this->_isFresh = $isFresh;
    }
}
