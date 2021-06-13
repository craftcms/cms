<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Craft;
use craft\elements\Asset;
use craft\elements\Asset as AssetElement;
use craft\elements\Category as CategoryElement;
use craft\elements\Entry as EntryElement;
use craft\elements\GlobalSet as GlobalSetElement;
use craft\elements\MatrixBlock as MatrixBlockElement;
use craft\elements\User as UserElement;
use craft\errors\GqlException;
use craft\gql\types\elements\Asset as AssetGqlType;
use craft\gql\types\elements\Category as CategoryGqlType;
use craft\gql\types\elements\Entry as EntryGqlType;
use craft\gql\types\elements\GlobalSet as GlobalSetGqlType;
use craft\gql\types\elements\MatrixBlock as MatrixBlockGqlType;
use craft\gql\types\elements\Tag as TagGqlType;
use craft\gql\types\elements\User as UserGqlType;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\CategoryGroup;
use craft\models\EntryType;
use craft\models\GqlSchema;
use craft\models\MatrixBlockType;
use craft\models\Section;
use craft\models\UserGroup;
use craft\services\Assets;
use craft\services\Deprecator;
use craft\volumes\Local;
use GraphQL\Type\Definition\ResolveInfo;
use League\Flysystem\Filesystem;

class FlysysteVolumeTest extends Unit
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
     * Test deprecation and caching.
     */
    public function testFileMetadataDeprecation()
    {
        /** @var Local $volume */
        $volume = $this->make(Local::class, [
            'filesystem' => $this->make(Filesystem::class, [
                'getMetadata' => Expected::exactly(2, [
                    'timestamp' => 123,
                    'size' => 456
                ])
            ])
        ]);

        $this->assertEquals(['timestamp' => 123, 'size' => 456], $volume->getFileMetadata('path'));
        $this->assertEquals(456, $volume->getFileSize('path'));
        $this->assertEquals(123, $volume->getDateModified('path'));
    }
}
