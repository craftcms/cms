<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\functional;

use Codeception\Example;
use Craft;
use craft\elements\User;
use craft\helpers\StringHelper;
use FunctionalTester;

/**
 * Test editing of various settings in Craft
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class SettingsEditCest
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
     * @param Example $example
     * @dataProvider editOptionsDataProvider
     */
    public function testEditData(FunctionalTester $I, Example $example)
    {
        $I->amOnPage('/'.$this->cpTrigger.''.$example['url'].'');
        $I->click($example['linkPropValue']);
        $I->see($example['linkPropValue']);

        $I->submitForm('#main-form', [$example['propName'] => $randString = StringHelper::randomString(10)]);

        $data = Craft::$app->{$example['craftAppProp']}->{$example['methodInvoker']}($example['methodProp']);
        $I->assertSame($randString, $data->{$example['propName']});
    }

    /**
     * @return array
     */
    protected function editOptionsDataProvider() : array
    {
        return [
            [
                'url' => '/settings/fields',
                'linkPropValue' => 'Example text field 1',
                'propName' => 'name',
                'craftAppProp' => 'fields',
                'methodInvoker' => 'getFieldByHandle',
                'methodProp' => 'exampleTextField1'
            ],
            [
                'url' => '/settings/sites',
                'linkPropValue' => 'Craft CMS testing',
                'propName' => 'name',
                'craftAppProp' => 'sites',
                'methodInvoker' => 'getSiteByHandle',
                'methodProp' => 'default'
            ],
            [
                'url' => '/settings/users',
                'linkPropValue' => 'Test group1',
                'propName' => 'name',
                'craftAppProp' => 'userGroups',
                'methodInvoker' => 'getGroupByHandle',
                'methodProp' => 'testGroup1'
            ]
        ];
    }
}
