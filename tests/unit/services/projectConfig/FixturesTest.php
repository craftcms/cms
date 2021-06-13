<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services\projectConfig;

use Codeception\Test\Unit;
use Craft;
use craft\helpers\StringHelper;
use craft\services\Fields;
use craft\services\Sections;
use crafttests\fixtures\EntryTypeFixture;
use crafttests\fixtures\EntryWithFieldsFixture;
use UnitTester;

/**
 * Unit tests for ProjectConfig service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.16
 */
class FixturesTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    public function _fixtures(): array
    {
        return [
            'entry-with-fields' => [
                'class' => EntryWithFieldsFixture::class
            ],
            'entry-types' => [
                'class' => EntryTypeFixture::class
            ],
        ];
    }


    /**
     * Tests whether setting a config value correctly appears in the database
     *
     * @dataProvider configChangesPropagatedToDbProvider
     */
    public function testConfigChangesPropagatedToDb($changes, $testFunction)
    {
        $projectConfig = Craft::$app->getProjectConfig();

        foreach ($changes as $path => $newValue) {
            $projectConfig->set($path, $newValue);
        }

        self::assertTrue($testFunction());
    }

    public function configChangesPropagatedToDbProvider()
    {
        $randomHandle = StringHelper::randomString(20);
        $sectionUid = 'section-1000---------------------uid';
        $entryTypeUid = 'entry-type-1000------------------uid';

        return [
            // Simple section handle check
            [
                ['sections.' . $sectionUid . '.handle' => 'newHandle'],
                function() use ($sectionUid) {
                    $sectionService = $this->make(Sections::class);
                    return $sectionService->getSectionByUid($sectionUid)->handle === 'newHandle';
                }
            ],
            // Entry type nesting inside section and handle
            [
                ['entryTypes.' . $entryTypeUid . '.handle' => $randomHandle],
                function() use ($sectionUid, $randomHandle, $entryTypeUid) {
                    $sectionService = $this->make(Sections::class);
                    return $sectionService->getEntryTypesByHandle($randomHandle)[0]->uid === $entryTypeUid;
                }
            ],
        ];
    }
}
