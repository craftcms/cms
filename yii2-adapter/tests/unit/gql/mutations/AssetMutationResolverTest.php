<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql\mutations;

use craft\gql\resolvers\mutations\Asset;
use craft\test\TestCase;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Support\Facades\Folders;
use GraphQL\Type\Definition\ResolveInfo;
use Throwable;

class AssetMutationResolverTest extends TestCase
{
    /**
     * Test whether various argument combos set the correct scenario on the element.
     *
     * @param array $arguments
     * @param string $exception
     * @throws Throwable
     * @dataProvider testVariousExceptionsProvider
     */
    public function testVariousExceptions(array $arguments, string $exception): void
    {
        $resolver = $this->make(Asset::class, [
            'requireSchemaAction' => null,
            'saveElement' => new Asset(),
            'recursivelyNormalizeArgumentValues' => $arguments,
        ]);

        $volume = new Volume();
        $volume->id = 1;
        $volume->uid = 'test-volume-uid';
        $resolver->setResolutionData('volume', $volume);

        $folder = new VolumeFolder(['id' => 1, 'volumeId' => 1]);

        Folders::shouldReceive('getRootFolderByVolumeId')
            ->with(1)
            ->andReturn($folder);
        Folders::shouldReceive('getFolderById')
            ->andReturn($folder);

        $this->expectExceptionMessage($exception);
        $resolver->saveAsset(null, $arguments, null, $this->make(ResolveInfo::class));
    }

    public function testVariousExceptionsProvider(): array
    {
        return [
            [['filename' => 'fake.jpg'], 'Impossible to create an asset without providing a file'],
            [['filename' => 'fake.jpg', '_file' => ['fileData' => 'this is not real base64 data']], 'Invalid file data provided'],
        ];
    }
}
