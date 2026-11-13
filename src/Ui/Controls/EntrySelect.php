<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Section\Data\Section;

/**
 * An {@see ElementSelect} for entries, whose search input can also create the entry it didn’t find.
 *
 * Deciding *whether* entries can be created is the field’s job — see {@see Entries::selectControl()} — because it
 * depends on the field’s source and selection condition, and on the user’s permissions in that section.
 *
 * @since 6.0.0
 */
class EntrySelect extends ElementSelect
{
    private ?string $createSection = null;

    /** Offers to create an entry in `$section` with the search input’s query as its title. */
    public function createSection(?Section $section): static
    {
        $this->createSection = $section?->handle;

        return $this;
    }

    /** @return array<string, mixed> */
    #[\Override]
    public function props(mixed $value = null): array
    {
        return [
            ...parent::props($value),
            'createSection' => $this->createSection,
        ];
    }
}
