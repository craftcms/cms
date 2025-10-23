<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use craft\records\Site;
use craft\test\ActiveFixture;
use CraftCms\Cms\Support\Facades\Sites;

/**
 * Class SitesFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class SitesFixture extends ActiveFixture
{
    /**
     * @inheritdoc
     */
    public $modelClass = Site::class;

    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/sites.php';

    /**
     * @inheritdoc
     */
    public function load(): void
    {
        parent::load();
        Sites::refreshSites();
    }

    /**
     * @inheritdoc
     */
    public function unload(): void
    {
        parent::unload();
        Sites::refreshSites();
    }
}
