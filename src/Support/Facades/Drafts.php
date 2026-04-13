<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \Illuminate\Support\Collection<\CraftCms\Cms\Element\Contracts\ElementInterface> getEditableDrafts(\CraftCms\Cms\Element\Contracts\ElementInterface $element, string|null $permission = null)
 * @method static \CraftCms\Cms\Element\Contracts\ElementInterface createDraft(\CraftCms\Cms\Element\Contracts\ElementInterface $canonical, int|null $creatorId = null, string|null $name = null, string|null $notes = null, array $newAttributes = [], bool $provisional = false)
 * @method static string generateDraftName(int $canonicalId)
 * @method static bool saveElementAsDraft(\CraftCms\Cms\Element\Contracts\ElementInterface $element, int|null $creatorId = null, string|null $name = null, string|null $notes = null, bool $markAsSaved = true)
 * @method static \CraftCms\Cms\Element\Contracts\ElementInterface applyDraft(\CraftCms\Cms\Element\Contracts\ElementInterface $draft, array $newAttributes = [])
 * @method static void removeDraftData(\CraftCms\Cms\Element\Contracts\ElementInterface $draft)
 * @method static void purgeUnsavedDrafts()
 * @method static int insertDraftRow(string|null $name, string|null $notes = null, int|null $creatorId = null, int|null $canonicalId = null, bool $trackChanges = false, bool $provisional = false)
 * @method static \CraftCms\Cms\Element\Contracts\ElementInterface[] withProvisionalDrafts(\CraftCms\Cms\Element\Contracts\ElementInterface[] $elements, \CraftCms\Cms\User\Elements\User|null $user = null)
 * @method static void loadProvisionalChanges(\CraftCms\Cms\Element\Contracts\ElementInterface[] $elements, \CraftCms\Cms\User\Elements\User|null $user = null)
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
