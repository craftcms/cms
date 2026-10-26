<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Release;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Release\Enums\ReleaseStatus;
use CraftCms\Cms\Release\Models\Release;
use CraftCms\Cms\Release\Models\ReleaseItem;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;

class ReleaseQueryFixture
{
    /** @param list<Entry> $drafts */
    public static function create(
        array $drafts,
        ReleaseStatus $status = ReleaseStatus::Draft,
    ): Release {
        $release = Release::query()->create([
            'name' => 'Query fixture release '.Str::random(8),
            'status' => $status,
            'createdById' => User::findOne()->id,
            'uid' => Str::uuid()->toString(),
        ]);

        foreach ($drafts as $draft) {
            ReleaseItem::query()->create([
                'releaseId' => $release->id,
                'draftId' => $draft->draftId,
                'canonicalId' => $draft->getCanonicalId(),
            ]);
        }

        return $release;
    }
}
