<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use Craft;
use craft\records\Section;
use craft\services\Sections;
use craft\test\ActiveFixture;

/**
 * Class SectionsFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class SectionsFixture extends ActiveFixture
{
    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/sections.php';

    /**
     * @inheritdoc
     */
    public $modelClass = Section::class;

    /**
     * @inheritdoc
     */
    public $depends = [SectionSettingFixture::class];

    /**
     * @inheritdoc
     */
    public function load(): void
    {
        parent::load();

        Craft::$app->set('sections', new Sections());
    }
}
