<?php

declare(strict_types=1);

namespace craft\mcp\contentmodel\checks;

use craft\models\Section;
use craft\services\Entries as Sections;
use Illuminate\Support\Collection;

/** @since 5.12.0 */
class EmptySections extends BaseCheck
{
    public function __construct(
        private readonly Sections $sections,
    ) {
    }

    public static function id(): string
    {
        return 'empty-sections';
    }

    protected function findings(): Collection
    {
        $sectionIds = $this->entryIdsBy('sectionId');

        return collect($this->sections->getAllSections())
            ->reject(static fn(Section $section): bool => isset($sectionIds[$section->id]))
            ->map($this->modelFinding(...))
            ->values();
    }
}
