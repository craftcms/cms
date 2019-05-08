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
 * Class SectionSettingFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
class SectionSettingFixture extends Fixture
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__.'/data/section-settings.php';

    /**
     * @inheritdoc
     */
    public $modelClass = Section_SiteSettings::class;
}