<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use craft\records\Section_SiteSettings;
use craft\test\ActiveFixture;

/**
 * Section site-settings used by NestedElementManagerTest (enables the NEM test section for the
 * default site + testSite1, so multi-site nested-element propagation can be exercised).
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class NemSectionSettingFixture extends ActiveFixture
{
    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/nem-section-settings.php';

    /**
     * @inheritdoc
     */
    public $modelClass = Section_SiteSettings::class;

    /**
     * @inheritdoc
     */
    public $depends = [SitesFixture::class];
}
