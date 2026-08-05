<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Contracts;

/**
 * MergeableFieldInterface defines the common interface to be implemented by field classes
 * that can be merged with other fields.
 */
interface MergeableFieldInterface extends FieldInterface
{
    /**
     * Returns whether the field can be merged into the given field.
     */
    public function canMergeInto(FieldInterface $persistingField, ?string &$reason): bool;

    /**
     * Returns whether the given field can be merged into this one.
     */
    public function canMergeFrom(FieldInterface $outgoingField, ?string &$reason): bool;

    /**
     * Performs actions after the field has been merged into the given field.
     */
    /** @return void */
    public function afterMergeInto(FieldInterface $persistingField);

    /**
     * Performs actions after the given field has been merged into this one.
     */
    /** @return void */
    public function afterMergeFrom(FieldInterface $outgoingField);
}
