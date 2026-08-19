<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use CraftCms\Cms\Form\Nodes\Field;
use Stringable;

readonly class AssetTransformDriverDefinition
{
    /**
     * @param  array<string, non-empty-list<string|Stringable>>  $operations
     * @param  list<Field>  $filesystemSettings
     * @param  array<string, Field>  $operationFields
     */
    public function __construct(
        public string $name,
        public array $operations = [],
        public array $filesystemSettings = [],
        public array $operationFields = [],
    ) {}
}
