<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use craft\base\ElementInterface;

/**
 * Request-scoped save optimization context.
 */
final class ElementSaveOptimizationContext
{
    public int $depth = 0;

    /**
     * @var array<string,bool>
     */
    private array $cacheInvalidationKeys = [];

    /**
     * @var array<string,bool>
     */
    private array $changedAttributeKeys = [];

    /**
     * @var array<string,bool>
     */
    private array $changedFieldKeys = [];

    /**
     * @var array<string,bool>
     */
    private array $ownerSearchIndexKeys = [];

    /**
     * @var array<string,bool>
     */
    private array $nestedSaveKeys = [];

    public function begin(): void
    {
        $this->depth++;
    }

    public function end(): bool
    {
        $this->depth--;
        return $this->depth === 0;
    }

    public function rememberCacheInvalidation(ElementInterface $element): bool
    {
        if (!isset($element->id)) {
            return true;
        }

        $key = sprintf('%s:%d', get_class($element), $element->id);

        if (isset($this->cacheInvalidationKeys[$key])) {
            return false;
        }

        $this->cacheInvalidationKeys[$key] = true;
        return true;
    }

    public function rememberChangedAttribute(
        int $elementId,
        int $siteId,
        string $attribute,
        bool $propagated,
        ?int $userId,
    ): bool {
        $key = sprintf('%d:%d:%s:%d:%d', $elementId, $siteId, $attribute, (int)$propagated, (int)$userId);

        if (isset($this->changedAttributeKeys[$key])) {
            return false;
        }

        $this->changedAttributeKeys[$key] = true;
        return true;
    }

    public function rememberChangedField(
        int $elementId,
        int $siteId,
        int $fieldId,
        string $layoutElementUid,
        bool $propagated,
        ?int $userId,
    ): bool {
        $key = sprintf(
            '%d:%d:%d:%s:%d:%d',
            $elementId,
            $siteId,
            $fieldId,
            $layoutElementUid,
            (int)$propagated,
            (int)$userId,
        );

        if (isset($this->changedFieldKeys[$key])) {
            return false;
        }

        $this->changedFieldKeys[$key] = true;
        return true;
    }

    public function rememberOwnerSearchIndex(int $ownerId, int $siteId, string $fieldHandle): bool
    {
        $key = sprintf('%d:%d:%s', $ownerId, $siteId, $fieldHandle);

        if (isset($this->ownerSearchIndexKeys[$key])) {
            return false;
        }

        $this->ownerSearchIndexKeys[$key] = true;
        return true;
    }

    public function rememberNestedSave(
        int $ownerId,
        int $ownerSiteId,
        string $managerKey,
        int $nestedId,
        int $sortOrder,
    ): bool {
        $key = sprintf('%d:%d:%s:%d:%d', $ownerId, $ownerSiteId, $managerKey, $nestedId, $sortOrder);

        if (isset($this->nestedSaveKeys[$key])) {
            return false;
        }

        $this->nestedSaveKeys[$key] = true;
        return true;
    }
}
