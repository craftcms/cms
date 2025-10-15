<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use yii\base\InvalidConfigException;

/**
 * NestedElementInterface defines the common interface to be implemented by elements that can be
 * nested within other elements via a custom field.
 *
 * [[NestedElementTrait]] provides a base implementation.
 *
 * @mixin ElementTrait
 * @mixin Component
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
interface NestedElementInterface extends ElementInterface
{
    /**
     * Returns the primary owner element’s ID, if the element has one.
     *
     * @return int|null
     * @throws InvalidConfigException if the element is misconfigured
     */
    public function getPrimaryOwnerId(): ?int;

    /**
     * Sets the primary owner element’s ID, if the element has one.
     *
     * @param int|null $id
     */
    public function setPrimaryOwnerId(?int $id): void;

    /**
     * Returns the primary owner element, if the element has one.
     *
     * @return ElementInterface|null
     * @throws InvalidConfigException if the element is misconfigured
     */
    public function getPrimaryOwner(): ?ElementInterface;

    /**
     * Sets the primary owner element, if the element has one.
     *
     * @param ElementInterface|null $owner
     */
    public function setPrimaryOwner(?ElementInterface $owner): void;

    /**
     * Returns the owner element’s ID, if the element has one.
     *
     * @return int|null
     * @throws InvalidConfigException if the element is misconfigured
     */
    public function getOwnerId(): ?int;

    /**
     * Sets the owner element’s ID, if the element has one.
     *
     * @param int|null $id
     */
    public function setOwnerId(?int $id): void;

    /**
     * Returns the owner element, if the element has one.
     *
     * @return ElementInterface|null
     * @throws InvalidConfigException if the element is misconfigured
     */
    public function getOwner(): ?ElementInterface;

    /**
     * Sets the owner element, if the element has one.
     *
     * @param ElementInterface|null $owner
     */
    public function setOwner(?ElementInterface $owner): void;

    /**
     * Returns each of the element’s owners
     *
     * @param array $criteria
     * @return ElementInterface[]
     * @throws InvalidConfigException if the element is misconfigured
     * @since 5.8.17
     */
    public function getOwners(array $criteria = []): array;

    /**
     * Returns the field that contains the element.
     *
     * @return ElementContainerFieldInterface|null
     * @throws InvalidConfigException if the element is misconfigured
     */
    public function getField(): ?ElementContainerFieldInterface;

    /**
     * Returns the element’s sort order, if it has one.
     *
     * @return int|null
     */
    public function getSortOrder(): ?int;

    /**
     * Sets the element’s sort order.
     *
     * @param int|null $sortOrder
     */
    public function setSortOrder(?int $sortOrder): void;

    /**
     * Sets whether the element’s ownership should be saved when the element is saved.
     *
     * @param bool $saveOwnership
     */
    public function setSaveOwnership(bool $saveOwnership): void;
}
