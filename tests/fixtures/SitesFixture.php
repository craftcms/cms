<?php
/**
 * Created by PhpStorm.
 * User: Giel Tettelaar
 * Date: 19/12/2018
 * Time: 14:20
 */

namespace craftunit\fixtures;


use Craft;
use craft\records\Site;
use craft\services\Sites;
use craft\test\Fixture;

class SitesFixture extends Fixture
{
    public $modelClass = Site::class;
    public $dataFile = __DIR__.'/data/sites.php';

    public function load()
    {
        parent::load();

        // Because the Sites() class memoizes on initialization we need to set() a new sites class
        // with the updated fixture data

        Craft::$app->set('sites', new Sites());
    }
}