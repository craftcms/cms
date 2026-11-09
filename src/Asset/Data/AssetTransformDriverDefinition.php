<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use CraftCms\Cms\Ui\Nodes\Field;
use Stringable;

/**
 * @since 6.0.0
 */
readonly class AssetTransformDriverDefinition
{
    /**
     * @param  string  $label  The driver’s display label.
     * @param  array<string, non-empty-list<string|Stringable>>  $parameterRules
     * @param  array<string, Field>  $parameterFields
     */
    public function __construct(
        public string $label,
        public array $parameterRules = [],
        public array $parameterFields = [],
    ) {}
}
