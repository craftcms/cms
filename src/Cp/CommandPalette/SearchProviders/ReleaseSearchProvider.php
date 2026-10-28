<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\CommandPalette\SearchProviders;

use CraftCms\Cms\Cp\CommandPalette\CommandPaletteItem;
use CraftCms\Cms\Cp\CommandPalette\Contracts\SearchProvider;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Release\Enums\ReleaseStatus;
use CraftCms\Cms\Release\Models\Release;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Database\Eloquent\Builder;

use function CraftCms\Cms\t;

/**
 * Finds content releases by name, unpublished ones first, for the people the
 * Releases section is shown to.
 *
 * @since 6.0.0
 */
readonly class ReleaseSearchProvider implements SearchProvider
{
    /** The most releases returned for one query. */
    private const int LIMIT = 10;

    public function getHandle(): string
    {
        return 'releases';
    }

    public function getLabel(): string
    {
        return t('Releases');
    }

    public function isAvailable(CraftUser $user): bool
    {
        return Edition::get()->supportsReleases()
            && (Sections::getTotalEditableSections() || $user->can('manageReleases'));
    }

    public function search(string $query, CraftUser $user): iterable
    {
        $words = array_filter(preg_split('/\s+/', trim($query)) ?: []);

        return Release::query()
            ->where(function (Builder $builder) use ($words): void {
                foreach ($words as $word) {
                    $builder->whereLike('name', "%{$word}%");
                }
            })
            ->orderByRaw('CASE WHEN status = ? THEN 1 ELSE 0 END', [ReleaseStatus::Published->value])
            ->orderByDesc('dateCreated')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Release $release) => new CommandPaletteItem()
                ->id("release-$release->id")
                ->label($release->name)
                ->hint($this->hint($release))
                ->description(t('Releases'))
                ->icon('rocket')
                ->href(route('craft.cp.releases.show', ['release' => $release->id])))
            ->all();
    }

    /** The release's status, with when it was published, or when it's planned for. */
    private function hint(Release $release): string
    {
        $date = $release->status === ReleaseStatus::Published ? $release->publishedAt : $release->targetDate;

        return $date
            ? sprintf('%s · %s', $release->status->label(), I18N::getFormatter()->asTimestamp($date, Locale::LENGTH_SHORT))
            : $release->status->label();
    }
}
