<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\ContentModel\Checks;

use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Sections;
use Illuminate\Support\Collection;

/** @since 6.0.0 */
class EmptySections extends BaseCheck
{
    public function __construct(
        private readonly Sections $sections,
    ) {}

    public static function id(): string
    {
        return 'empty-sections';
    }

    protected function findings(): Collection
    {
        $sectionIds = $this->entryIdsBy('sectionId');

        return $this->sections
            ->getAllSections()
            ->reject(static fn (Section $section): bool => isset($sectionIds[$section->id]))
            ->map($this->modelFinding(...))
            ->values();
    }
}
