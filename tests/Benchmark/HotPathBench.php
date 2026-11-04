<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Benchmark;

use CraftCms\Cms\Cp\Cp;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Support\Once;
use PhpBench\Attributes as Bench;

use function CraftCms\Cms\t;

/**
 * Code that runs on most control panel requests, measured without the noise of booting the application.
 *
 * These are memoized for the rest of a request, so each benchmark clears its memo and measures what the first call
 * in a request costs. Compared with a baseline (`--ref`), each fails once it's slower by more than its own threshold.
 */
#[Bench\Revs(20)]
#[Bench\Iterations(10)]
#[Bench\Warmup(1)]
#[Bench\Groups(['hotpath'])]
#[Bench\OutputTimeUnit('milliseconds', precision: 3)]
#[Bench\BeforeMethods('boot')]
class HotPathBench extends BenchCase
{
    private User $editor;

    /** @var string[] */
    private array $permissions;

    public function boot(): void
    {
        $app = $this->createApplication();
        $this->editor = User::query()->where('username', 'editor')->firstOrFail();
        $this->actingAs($app, $this->editor);

        // What an entries index or a dashboard checks for each section: some held, some not
        $this->permissions = Section::query()->pluck('uid')
            ->flatMap(fn (string $uid) => ["viewEntries:$uid", "saveEntries:$uid", "viewPeerEntryDrafts:$uid", "deleteEntries:$uid"])
            ->all();
    }

    /**
     * The control panel's config, which every Inertia response shares.
     */
    #[Bench\Assert('mode(variant.time.avg) <= mode(baseline.time.avg) +/- 3 milliseconds')]
    public function benchCpConfig(): void
    {
        Once::flush();
        Cp::config();
    }

    /**
     * A non-admin's permission checks across every section.
     */
    #[Bench\Assert('mode(variant.time.avg) <= mode(baseline.time.avg) +/- 1 milliseconds')]
    public function benchPermissionChecks(): void
    {
        $userPermissions = app()->build(UserPermissions::class);

        foreach ($this->permissions as $permission) {
            $userPermissions->doesUserHavePermission($this->editor->id, $permission);
        }
    }

    /**
     * Building every field layout, as the entries index and field settings do.
     */
    #[Bench\Assert('mode(variant.time.avg) <= mode(baseline.time.avg) +/- 10 milliseconds')]
    public function benchFieldLayouts(): void
    {
        $fields = app(Fields::class);
        $fields->invalidateCaches();
        $fields->getAllLayouts();
    }

    /**
     * Translating messages with parameters, which skip the memo of translated strings.
     */
    #[Bench\Assert('mode(variant.time.avg) <= mode(baseline.time.avg) +/- 1 milliseconds')]
    public function benchTranslate(): void
    {
        for ($i = 0; $i < 100; $i++) {
            t('{count} entries', ['count' => $i]);
        }
    }
}
