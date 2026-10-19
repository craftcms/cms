<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Contracts;

/**
 * InputSchemaFieldInterface defines the common interface to be implemented by field classes that describe the values
 * they accept with a JSON schema.
 *
 * @since 6.0.0
 */
interface InputSchemaFieldInterface extends FieldInterface
{
    /**
     * Returns the JSON schema of the values the field accepts.
     *
     * @return array<string, mixed>
     */
    public function getInputSchema(): array;
}
