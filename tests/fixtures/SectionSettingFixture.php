<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace craftunit\fixtures;


use craft\records\Section_SiteSettings;
use craft\test\Fixture;

/**
 * Unit tests for SectionSettingFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class SectionSettingFixture extends Fixture
{
    public $dataFile = __DIR__.'/data/section-settings.php';
    public $modelClass = Section_SiteSettings::class;
}