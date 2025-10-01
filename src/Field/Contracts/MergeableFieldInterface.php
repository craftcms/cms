<?php

namespace CraftCms\Cms\Field\Contracts;

/**
 * MergeableFieldInterface defines the common interface to be implemented by field classes
 * that can be merged with other fields.
 *
 * @since 6.0.0
 */
interface MergeableFieldInterface extends FieldInterface
{
    /**
     * Returns whether the field can be merged into the given field.
     *
     * @param FieldInterface $persistingField
     * @param string|null $reason
     * @return bool
     */
    public function canMergeInto(FieldInterface $persistingField, ?string &$reason): bool;

    /**
     * Returns whether the given field can be merged into this one.
     *
     * @param FieldInterface $outgoingField
     * @param string|null $reason
     * @return bool
     */
    public function canMergeFrom(FieldInterface $outgoingField, ?string &$reason): bool;

    /**
     * Performs actions after the field has been merged into the given field.
     *
     * @param FieldInterface $persistingField
     */
    public function afterMergeInto(FieldInterface $persistingField);

    /**
     * Performs actions after the given field has been merged into this one.
     *
     * @param FieldInterface $outgoingField
     */
    public function afterMergeFrom(FieldInterface $outgoingField);
}
