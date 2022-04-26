<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use craft\volumes\Local;
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
                    'size' => 456,
                ]),
            ]),
        ]);

        $this->assertEquals(['timestamp' => 123, 'size' => 456], $volume->getFileMetadata('path'));
        $this->assertEquals(456, $volume->getFileSize('path'));
        $this->assertEquals(123, $volume->getDateModified('path'));
    }
}
