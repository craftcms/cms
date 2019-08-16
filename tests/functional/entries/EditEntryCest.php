<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\functional\users;

use Craft;
use craft\elements\User;
use FunctionalTester;

/**
 * Test editing of entries.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class EditEntryCest
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $cpTrigger;

    /**
     * @var
     */
    public $currentUser;

    // Public Methods
    // =========================================================================

    /**
     * @param FunctionalTester $I
     */
    public function _before(FunctionalTester $I)
    {
        $this->currentUser = User::find()
            ->admin()
            ->one();

        $I->amLoggedInAs($this->currentUser);
        $this->cpTrigger = Craft::$app->getConfig()->getGeneral()->cpTrigger;
    }

    // Tests
    // =========================================================================

    /**
     * @param FunctionalTester $I
     */
    public function seeSections(FunctionalTester $I)
    {
        $I->amOnPage('/'.$this->cpTrigger.'/entries');

        $I->see('Craft CMS Test Section');
    }
}
