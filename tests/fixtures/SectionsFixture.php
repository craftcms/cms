<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\fixtures;

use Craft;
use craft\records\Section as SectionRecord;
use craft\records\Section;
use craft\services\Sections;
use craft\test\Fixture;

/**
 * Unit tests for SectionsFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class SectionsFixture extends Fixture
{
    public $dataFile = __DIR__.'/data/sections.php';
    public $modelClass = SectionRecord::class;
    public $depends = [SectionSettingFixture::class];

    public function load()
    {
        parent::load();

        Craft::$app->set('sections', new Sections());
    }
}