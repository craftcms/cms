<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Matrix;
use Illuminate\Http\JsonResponse;

/** @since 6.0.0 */
class MatrixBlockHtmlRendering
{
    public ?JsonResponse $response = null;

    /** @param list<Entry> $entries */
    public function __construct(
        public array $entries,
        public ?Matrix $field,
        public string $namespace,
        public bool $fresh = false,
        public bool $staticEntries = false,
    ) {}
}
