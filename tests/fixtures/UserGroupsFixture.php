<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use Craft;
use craft\records\UserGroup;
use craft\services\UserGroups;
use craft\test\Fixture;
use yii\base\Exception;

/**
 * Class UserGroupsFixture.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class UserGroupsFixture extends Fixture
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $modelClass = UserGroup::class;

    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/user-groups.php';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function load()
    {
        parent::load();

        Craft::$app->set('userGroups', new UserGroups());
    }
}
