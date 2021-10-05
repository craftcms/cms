<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql\mutations;

use craft\base\Element;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\gql\resolvers\mutations\Asset;
use craft\gql\resolvers\mutations\Entry as EntryMutationResolver;
use craft\models\VolumeFolder;
use craft\records\Volume;
use craft\services\Assets;
use craft\services\Elements;
use craft\test\TestCase;
use GraphQL\Type\Definition\ResolveInfo;

class AssetMutationResolverTest extends TestCase
{
    /**
     * Test whether various argument combos set the correct scenario on the element.
     * 
     * @param $arguments
     * @param $scenario
     * @throws \Throwable
     * @dataProvider testVariousExceptionsProvider
     */
    public function testVariousExceptions($arguments, $exception)
    {
        $resolver = $this->make(Asset::class, [
            'requireSchemaAction' => null,
            'saveElement' => new Asset(),
            'recursivelyNormalizeArgumentValues' => $arguments
        ]);
        $resolver->setResolutionData('volume', new Volume(['id' => 1]));

        $folder = new VolumeFolder(['id' => 1, 'volumeId' => 1]);
        \Craft::$app->set('assets', $this->make(Assets::class, [
            'getRootFolderByVolumeId' => $folder,
            'getFolderById' => $folder
        ]));


        if ($exception) {
            $this->expectExceptionMessage($exception);
        }

        $resolver->saveAsset(null, $arguments, null, $this->make(ResolveInfo::class));
    }

    public function testVariousExceptionsProvider()
    {
        return [
            [['filename' => 'fake.jpg'], 'Impossible to create an asset without providing a file'],
            [['filename' => 'fake.jpg', '_file' => ['fileData' => 'this is not real base64 data']], 'Invalid file data provided']
        ];
    }
}
