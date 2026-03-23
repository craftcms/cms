<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \Illuminate\Support\Collection getEditableDrafts(\craft\base\ElementInterface $element, string|null $permission = null)
 * @method static mixed createDraft(mixed $canonical, int|null $creatorId = null, string|null $name = null, string|null $notes = null, array $newAttributes = [], bool $provisional = false)
 * @method static string generateDraftName(int $canonicalId)
 * @method static bool saveElementAsDraft(\craft\base\ElementInterface $element, int|null $creatorId = null, string|null $name = null, string|null $notes = null, bool $markAsSaved = true)
 * @method static mixed applyDraft(mixed $draft, array $newAttributes = [])
 * @method static void removeDraftData(\craft\base\ElementInterface $draft)
 * @method static void purgeUnsavedDrafts()
 * @method static int insertDraftRow(string|null $name, string|null $notes = null, int|null $creatorId = null, int|null $canonicalId = null, bool $trackChanges = false, bool $provisional = false)
 *
 * @see \CraftCms\Cms\Element\Drafts
 */
class Drafts extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Element\Drafts::class;
    }
}
