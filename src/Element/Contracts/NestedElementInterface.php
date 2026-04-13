<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Contracts;

use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use RuntimeException;

/**
 * NestedElementInterface defines the common interface to be implemented by elements that can be
 * nested within other elements via a custom field.
 */
interface NestedElementInterface extends ElementInterface
{
    /**
     * Returns the primary owner element’s ID, if the element has one.
     *
     * @throws RuntimeException if the element is misconfigured
     */
    #[AllowedInSandbox]
    public function getPrimaryOwnerId(): ?int;

    /**
     * Sets the primary owner element’s ID, if the element has one.
     */
    public function setPrimaryOwnerId(?int $id): void;

    /**
     * Returns the primary owner element, if the element has one.
     *
     * @throws RuntimeException if the element is misconfigured
     */
    public function getPrimaryOwner(): ?ElementInterface;

    /**
     * Sets the primary owner element, if the element has one.
     */
    public function setPrimaryOwner(?ElementInterface $owner): void;

    /**
     * Returns the owner element’s ID, if the element has one.
     *
     * @throws RuntimeException if the element is misconfigured
     */
    #[AllowedInSandbox]
    public function getOwnerId(): ?int;

    /**
     * Sets the owner element’s ID, if the element has one.
     */
    public function setOwnerId(?int $id): void;

    /**
     * Returns the owner element, if the element has one.
     *
     * @throws RuntimeException if the element is misconfigured
     */
    public function getOwner(): ?ElementInterface;

    /**
     * Sets the owner element, if the element has one.
     */
    public function setOwner(?ElementInterface $owner): void;

    /**
     * Returns each of the element’s owners
     *
     * @return ElementInterface[]
     *
     * @throws RuntimeException if the element is misconfigured
     */
    public function getOwners(array $criteria = []): array;

    /**
     * Returns the field that contains the element.
     *
     * @throws RuntimeException if the element is misconfigured
     */
    public function getField(): ?ElementContainerFieldInterface;

    /**
     * Returns the element’s sort order, if it has one.
     */
    public function getSortOrder(): ?int;

    /**
     * Sets the element’s sort order.
     */
    public function setSortOrder(?int $sortOrder): void;

    /**
     * Sets whether the element’s ownership should be saved when the element is saved.
     */
    public function setSaveOwnership(bool $saveOwnership): void;
}
