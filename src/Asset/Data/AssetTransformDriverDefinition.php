<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use CraftCms\Cms\Form\Nodes\Field;
use Stringable;

readonly class AssetTransformDriverDefinition
{
    /**
     * @param  array<string, non-empty-list<string|Stringable>>  $parameterRules
     * @param  list<Field>  $settingsFields
     * @param  array<string, Field>  $parameterFields
     */
    public function __construct(
        public string $name,
        public array $parameterRules = [],
        public array $settingsFields = [],
        public array $parameterFields = [],
    ) {}
}
