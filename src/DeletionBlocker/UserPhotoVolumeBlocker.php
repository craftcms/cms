<?php

declare(strict_types=1);

namespace CraftCms\Cms\DeletionBlocker;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use Illuminate\Support\Facades\DB;

use function CraftCms\Cms\t;

/**
 * Reports when a volume is the user photo volume, and how many users have a photo in it.
 *
 * @since 6.0.0
 */
class UserPhotoVolumeBlocker extends BaseResolvableDeletionBlocker
{
    /** @param array<string, mixed> $config */
    public function __construct(
        protected readonly Volume $volume,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function getKey(): string
    {
        return 'userPhotos';
    }

    public function getSummary(): string
    {
        return t('The volume stores user photos.');
    }

    public function getResolutionLabel(): string
    {
        return t('Stop storing user photos');
    }

    protected function getResolvedMessage(): string
    {
        return t('Users won’t be able to upload photos until another volume is chosen in the user settings.');
    }

    protected function defineItems(): array
    {
        if (ProjectConfig::get('users.photoVolumeUid') !== $this->volume->uid) {
            return [];
        }

        $count = DB::table(Table::USERS, 'users')
            ->join(Table::ASSETS.' as assets', 'assets.id', '=', 'users.photoId')
            ->join(Table::ELEMENTS.' as elements', 'elements.id', '=', 'users.id')
            ->where('assets.volumeId', $this->volume->id)
            ->whereNull('elements.dateDeleted')
            ->count();

        return [
            [
                'label' => t('User Photos'),
                'count' => $count,
                'description' => t('{count, number} {count, plural, =1{user has} other{users have}} a photo in this volume.', [
                    'count' => $count,
                ]),
            ],
        ];
    }
}
