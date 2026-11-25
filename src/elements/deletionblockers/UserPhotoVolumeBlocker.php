<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\deletionblockers;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\models\Volume;

/**
 * UserPhotoVolumeBlocker reports when a volume is the user photo volume, and how many users have a photo in it.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class UserPhotoVolumeBlocker extends BaseResolvableDeletionBlocker
{
    /**
     * Constructor
     */
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
        return Craft::t('app', 'The volume stores user photos.');
    }

    public function getResolutionLabel(): string
    {
        return Craft::t('app', 'Stop storing user photos');
    }

    protected function getResolvedMessage(): string
    {
        return Craft::t('app', 'Users won’t be able to upload photos until another volume is chosen in the user settings.');
    }

    protected function defineItems(): array
    {
        if (Craft::$app->getProjectConfig()->get('users.photoVolumeUid') !== $this->volume->uid) {
            return [];
        }

        $count = (int)(new Query())
            ->from(['users' => Table::USERS])
            ->innerJoin(['assets' => Table::ASSETS], '[[assets.id]] = [[users.photoId]]')
            ->innerJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[users.id]]')
            ->where([
                'assets.volumeId' => $this->volume->id,
                'elements.dateDeleted' => null,
            ])
            ->count();

        return [
            [
                'label' => Craft::t('app', 'User Photos'),
                'count' => $count,
                'description' => Craft::t('app', '{count, number} {count, plural, =1{user has} other{users have}} a photo in this volume.', [
                    'count' => $count,
                ]),
            ],
        ];
    }
}
