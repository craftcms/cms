<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \Illuminate\Support\Collection getEditableDrafts(\craft\base\ElementInterface $element, ?string $permission = null)
 * @method static \craft\base\ElementInterface createDraft(\craft\base\ElementInterface $canonical, ?int $creatorId = null, ?string $name = null, ?string $notes = null, array $newAttributes = [], bool $provisional = false)
 * @method static string generateDraftName(int $canonicalId)
 * @method static bool saveElementAsDraft(\craft\base\ElementInterface $element, ?int $creatorId = null, ?string $name = null, ?string $notes = null, bool $markAsSaved = true)
 * @method static \craft\base\ElementInterface applyDraft(\craft\base\ElementInterface $draft, array $newAttributes = [])
 * @method static void removeDraftData(\craft\base\ElementInterface $draft)
 * @method static void purgeUnsavedDrafts()
 * @method static int insertDraftRow(?string $name, ?string $notes = null, ?int $creatorId = null, ?int $canonicalId = null, bool $trackChanges = false, bool $provisional = false)
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
