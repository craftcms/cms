<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql\mutations;

use Codeception\Stub\Expected;
use craft\base\Volume;
use craft\elements\Asset;
use craft\gql\resolvers\mutations\Asset as AssetResolver;
use craft\helpers\StringHelper;
use craft\models\VolumeFolder;
use craft\test\mockclasses\elements\MockElementQuery;
use craft\test\TestCase;
use GraphQL\Type\Definition\ResolveInfo;
use GuzzleHttp\Client;

class ResolveAssetMutationsTest extends TestCase
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    /**
     * For given arguments, ensure that exceptions are thrown correctly.
     *
     * @param array $arguments
     * @param string $exception
     * @param bool $rootFolderUsed whether the root folder of a volume should be used
     * @param bool $volumeChanged whether the volume changed for the asset
     * @throws \Exception
     * @dataProvider saveAssetDataProvider
     */
    public function testSaveAsset($arguments = [], $exception = '', $rootFolderUsed = false, $volumeChanged = false)
    {
        $volumeId = random_int(1, 1000);
        $assetId = random_int(1, 1000);
        $folderId = random_int(1, 1000);
        $rootFolderId = random_int(1, 1000);

        $asset = new Asset([
            'volumeId' => $volumeId,
            'id' => $assetId,
        ]);

        $canIdentify = !empty($arguments['id']) || !empty($arguments['uid']);

        $this->tester->mockCraftMethods('elements', [
            'getElementById' => !empty($arguments['id']) && $arguments['id'] < 0 ? null : $asset,
            'createElementQuery' => (new MockElementQuery())->setReturnValues([$asset]),
            'createElement' => function(array $config) {
                unset($config['type']);
                return new Asset($config);
            }
        ]);

        /**
         * folderId < 0 means no folder found, an id between 0 and 10 will return a folder in the correct volume
         * Otherwise, return a folder in the wrong volume
         */
        $this->tester->mockCraftMethods('assets', [
            'getFolderById' => function($id) use ($volumeId) {
                if ($id < 0) {
                    return null;
                }

                return new VolumeFolder(['volumeId' => $volumeId]);
            },
            'getRootFolderByVolumeId' => $rootFolderUsed ? Expected::once(new VolumeFolder(['volumeId' => $volumeId, 'id' => $rootFolderId])) : null,
        ]);

        $mockVolume = $this->make(Volume::class, [
                'uid' => StringHelper::UUID(),
                'id' => $volumeId + (int)$volumeChanged
            ]
        );

        $resolver = $this->make(AssetResolver::class, [
            'requireSchemaAction' => function($scope, $action) use ($canIdentify) {
                $this->assertSame($canIdentify ? 'save' : 'create', $action);
            },
            'getResolutionData' => $mockVolume,
            'handleUpload' => true,
            'saveElement' => function($assetToSave) use ($assetId) {
                $assetToSave->id = $assetToSave->id ?? $assetId;
                return $assetToSave;
            },
        ]);

        if ($exception) {
            $this->expectExceptionMessage($exception);
        }

        $asset = $resolver->saveAsset(null, $arguments, null, $this->make(ResolveInfo::class));

        // For identifiable assets, don't change the folder automatically, if the volume does not change
        if ((!empty($arguments['id']) || !empty($arguments['uid'])) && !$volumeChanged) {
            $this->assertEquals($asset->newFolderId, $arguments['newFolderId'] ?? null);
        }

        $this->assertSame($mockVolume->id, $asset->getVolumeId());
    }

    /**
     * Test deleting an asset checks for schema and calls the Element service.
     *
     * @throws \Exception
     */
    public function testDeleteAsset()
    {
        $this->tester->mockCraftMethods('elements', [
            'getElementById' => Expected::once(new Asset(['volumeId' => 2])),
            'deleteElementById' => Expected::once(true)
        ]);

        $resolver = $this->make(AssetResolver::class, [
            'requireSchemaAction' => Expected::once(true)
        ]);

        $resolver->deleteAsset(null, ['id' => 2], null, $this->make(ResolveInfo::class));
    }

    /**
     * Test that if Asset is not found, the logic exits early.
     *
     * @throws \Throwable
     */
    public function testDeleteAssetNotFound()
    {
        $this->tester->mockCraftMethods('elements', [
            'getElementById' => Expected::once(false),
            'deleteElementById' => Expected::never()
        ]);
        $resolver = $this->make(AssetResolver::class, [
            'requireSchemaAction' => Expected::never()
        ]);

        $resolver->deleteAsset(null, ['id' => 2], null, $this->make(ResolveInfo::class));
    }

    /**
     * Test if asset correctly is populated with data according to arguments.
     *
     * @param $arguments
     * @param $scenario
     * @param $fieldValues
     * @throws \ReflectionException
     * @dataProvider assetPopulatingWithDataProvider
     */
    public function testAssetPopulatingWithData($arguments, $scenario, $fieldValues)
    {
        $handleUploadResult = !empty($arguments['_file']) ? Expected::once(true) : false;

        $resolver = $this->make(AssetResolver::class, [
            'handleUpload' => $handleUploadResult
        ]);

        $asset = new Asset();

        if (!empty($arguments['id'])) {
            $asset->id = $arguments['id'];
        }

        $asset = $this->invokeMethod($resolver, 'populateElementWithData', [$asset, $arguments]);

        $this->assertSame($scenario, $asset->getScenario());

        foreach ($fieldValues as $field => $value) {
            $this->assertSame($value, $asset->{$field});
        }
    }

    /**
     * Test whether uploading a file via GraphQL work as expected.
     *
     * @param array $fileInformation
     * @param bool $result
     * @param array $properties
     * @param $exception
     * @throws \ReflectionException
     * @dataProvider handleUploadDataProvider
     */
    public function testHandleUpload($fileInformation = [], $result = true, $properties = [], string $exception = null)
    {
        $resolver = $this->make(AssetResolver::class, [
            'createGuzzleClient' => $this->make(Client::class, [
                'request' => null
            ])
        ]);

        $asset = new Asset();

        // Test exception message
        if ($exception) {
            $this->expectExceptionMessage($exception);
        }
        $handleUploadResult = $this->invokeMethod($resolver, 'handleUpload', [$asset, $fileInformation]);

        // Check if correct result
        $this->assertSame($result, $handleUploadResult);

        // And properties match
        if (!empty($properties)) {
            foreach ($properties as $property => $value) {
                $this->assertEquals($value, $asset->{$property});
            }
        }

        // Check if temp file exists. And kill it.
        if (!empty($asset->tempFilePath)) {
            $this->assertFileExists($asset->tempFilePath);
            @unlink($asset->tempFilePath);
        }
    }

    public function assetPopulatingWithDataProvider()
    {
        return [
            [
                [
                    '_file' => [true],
                    'title' => 'someAsset',
                ],
                Asset::SCENARIO_CREATE,
                ['title' => 'someAsset']
            ],
            [
                [
                    '_file' => [true],
                    'title' => 'someAsset2',
                    'id' => 88
                ],
                Asset::SCENARIO_REPLACE,
                [
                    'title' => 'someAsset2',
                    'id' => 88
                ]
            ],
            [
                [
                    'title' => 'someAsset',
                ],
                Asset::SCENARIO_DEFAULT,
                ['title' => 'someAsset']
            ],
        ];
    }

    public function handleUploadDataProvider()
    {
        return [
            [
                // No data
                [], false, [], null
            ],
            [
                // Empty data
                ['fileData' => ''], false, [], null
            ],
            [
                // upload via filedata, default filename
                [
                    'fileData' => 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBzdGFuZGFsb25lPSJubyI/Pgo8c3ZnIHdpZHRoPSIxOHB4IiBoZWlnaHQ9IjE4cHgiIHZpZXdCb3g9IjAgMCAxOCAxOCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2ZXJzaW9uPSIxLjEiPgogIDxjaXJjbGUgY3g9IjkiIGN5PSI5IiByPSI4IiBmaWxsPSIjMDAwMDAwIiBmaWxsLW9wYWNpdHk9IjAuNSIgc3Ryb2tlPSIjZmZmZmZmIiBzdHJva2Utb3BhY2l0eT0iMC44IiBzdHJva2Utd2lkdGg9IjIiICAvPgogIDxjaXJjbGUgY3g9IjkiIGN5PSI5IiByPSIxIiBmaWxsPSIjZmZmZmZmIiBmaWxsLW9wYWNpdHk9IjAuOCIgc3Ryb2tlPSIjZmZmZmZmIiBzdHJva2Utb3BhY2l0eT0iMC44IiBzdHJva2Utd2lkdGg9IjIiICAvPgo8L3N2Zz4='
                ],
                true,
                [
                    'filename' => 'Upload.svg',
                    'avoidFilenameConflicts' => true
                ],
                null
            ],
            [
                // Upload via filedata, handpicked name
                [
                    'fileData' => 'data:image/jpeg;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBzdGFuZGFsb25lPSJubyI/Pgo8c3ZnIHdpZHRoPSIxOHB4IiBoZWlnaHQ9IjE4cHgiIHZpZXdCb3g9IjAgMCAxOCAxOCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2ZXJzaW9uPSIxLjEiPgogIDxjaXJjbGUgY3g9IjkiIGN5PSI5IiByPSI4IiBmaWxsPSIjMDAwMDAwIiBmaWxsLW9wYWNpdHk9IjAuNSIgc3Ryb2tlPSIjZmZmZmZmIiBzdHJva2Utb3BhY2l0eT0iMC44IiBzdHJva2Utd2lkdGg9IjIiICAvPgogIDxjaXJjbGUgY3g9IjkiIGN5PSI5IiByPSIxIiBmaWxsPSIjZmZmZmZmIiBmaWxsLW9wYWNpdHk9IjAuOCIgc3Ryb2tlPSIjZmZmZmZmIiBzdHJva2Utb3BhY2l0eT0iMC44IiBzdHJva2Utd2lkdGg9IjIiICAvPgo8L3N2Zz4=',
                    'filename' => 'file.jpg',
                ],
                true,
                [
                    'filename' => 'file.jpg',
                    'avoidFilenameConflicts' => true
                ],
                null
            ],
            [
                // Upload via filedata, infer name, unknown file type
                ['fileData' => 'data:image/foobarxyz;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBzdGFuZGFsb25lPSJubyI/Pgo8c3ZnIHdpZHRoPSIxOHB4IiBoZWlnaHQ9IjE4cHgiIHZpZXdCb3g9IjAgMCAxOCAxOCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2ZXJzaW9uPSIxLjEiPgogIDxjaXJjbGUgY3g9IjkiIGN5PSI5IiByPSI4IiBmaWxsPSIjMDAwMDAwIiBmaWxsLW9wYWNpdHk9IjAuNSIgc3Ryb2tlPSIjZmZmZmZmIiBzdHJva2Utb3BhY2l0eT0iMC44IiBzdHJva2Utd2lkdGg9IjIiICAvPgogIDxjaXJjbGUgY3g9IjkiIGN5PSI5IiByPSIxIiBmaWxsPSIjZmZmZmZmIiBmaWxsLW9wYWNpdHk9IjAuOCIgc3Ryb2tlPSIjZmZmZmZmIiBzdHJva2Utb3BhY2l0eT0iMC44IiBzdHJva2Utd2lkdGg9IjIiICAvPgo8L3N2Zz4=',],
                true,
                [],
                'Invalid file data provided',
            ],
            [
                // Upload via filedata, messed up format
                ['fileData' => 'dasta:images/file',],
                true,
                [],
                'Invalid file data provided',
            ],
            [
                // Upload via URL, inferred name
                ['url' => 'http://testtest.test/file.jpg?something&different=fine#hash',],
                true,
                [
                    'filename' => 'file.jpg',
                    'avoidFilenameConflicts' => true
                ],
                null
            ],
            [
                // Upload via URL, handpicked name
                [
                    'url' => 'http://testtest.test/file.jpg?something&different=fine#hash',
                    'filename' => 'otherFile.gif',
                ],
                true,
                [
                    'filename' => 'otherFile.gif',
                    'avoidFilenameConflicts' => true
                ],
                null
            ],
        ];
    }

    public function saveAssetDataProvider()
    {
        return [
            [
                // Save asset
                ['id' => 7],
                '',
                true
            ],
            [
                // Attempt to save a missing asset
                ['id' => -7],
                'No such asset exists',
            ],
            [
                // Create a new asset without file
                ['title' => 'someAsset'],
                'Impossible to create an asset without providing a file',
            ],
            [
                // Provide a folder id from a different volume
                ['title' => 'someAsset', '_file' => ['something'], 'newFolderId' => 5],
                'Invalid folder id provided',
                false,
                true
            ],
            [
                // Provide a folder id for a non-existing folder
                ['title' => 'someAsset', '_file' => ['something'], 'newFolderId' => -5],
                'Invalid folder id provided'
            ],
            [
                // Move to a new folder
                ['id' => 7, 'newFolderId' => 7],
            ],
            [
                // Move to a folder that is in a different volume
                ['id' => 7, 'newFolderId' => 5],
                'Invalid folder id provided',
                false,
                true
            ],
            [
                // Upload and provide target folder
                ['title' => 'someAsset', '_file' => ['something'], 'newFolderId' => 5],
            ],
            [
                // Create a new asset in the root folder
                ['title' => 'someAsset', '_file' => ['something']],
                '',
                true
            ],
            [
                // Move to a root folder that is in a different volume
                ['id' => 7],
                '',
                true,
                true
            ],
        ];
    }

}
