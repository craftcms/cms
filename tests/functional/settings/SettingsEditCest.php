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
use craft\helpers\ArrayHelper;
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
     * @dataProvider editOptionsDataProvider
     *
     * @param FunctionalTester $I
     * @param Example $example
     */
    public function testEditData(FunctionalTester $I, Example $example)
    {
        $I->amOnPage('/'.$this->cpTrigger.$example['url']);
        $I->click($example['linkPropValue']);
        $I->see($example['linkPropValue']);
        $I->seeInTitle($example['linkPropValue']);

        $postData = [$example['propName'] => $randString = StringHelper::randomString(10)];
        if (isset($example['additionalPostData'])) {
            $postData = ArrayHelper::merge(
                $postData,
                $example['additionalPostData']
            );
        }

        $I->submitForm('#main-form', $postData);
        //Craft::$app->saveInfoAfterRequestHandler();
        $data = Craft::$app->{$example['craftAppProp']}->{$example['methodInvoker']}($example['methodProp']);
        //Craft::$app->saveInfoAfterRequestHandler();
        $I->assertSame($randString, $data->{$example['propName']});
    }

    // Protected Methods
    // =========================================================================

    // Data Providers
    // =========================================================================

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
            // @todo Fix this.
//            [
//                'url' => '/settings/sites',
//                'linkPropValue' => 'Craft CMS Test Site',
//                'propName' => 'name',
//                'craftAppProp' => 'sites',
//                'methodInvoker' => 'getSiteByHandle',
//                'methodProp' => 'default'
//            ],
            [
                'url' => '/settings/users',
                'linkPropValue' => 'Test group1',
                'propName' => 'name',
                'craftAppProp' => 'userGroups',
                'methodInvoker' => 'getGroupByHandle',
                'methodProp' => 'testGroup1'
            ],
            [
                'url' => '/settings/assets/transforms',
                'linkPropValue' => 'Example transform 1',
                'propName' => 'name',
                'craftAppProp' => 'assetTransforms',
                'methodInvoker' => 'getTransformByHandle',
                'methodProp' => 'exampleTransform'
            ],
            [
                'url' => '/settings/sections',
                'linkPropValue' => 'Craft CMS Test Section',
                'propName' => 'name',
                'craftAppProp' => 'sections',
                'methodInvoker' => 'getSectionByHandle',
                'methodProp' => 'craftCmsTestSection'
            ],
            [
                'url' => '/settings/tags',
                'linkPropValue' => 'Test tag group 1',
                'propName' => 'name',
                'craftAppProp' => 'tags',
                'methodInvoker' => 'getTagGroupByHandle',
                'methodProp' => 'testTaggroup1'
            ],
            [
                'url' => '/settings/categories',
                'linkPropValue' => 'Test category group 1',
                'propName' => 'name',
                'craftAppProp' => 'categories',
                'methodInvoker' => 'getGroupByHandle',
                'methodProp' => 'testCategoryGroup1',
                'additionalPostData' => [
                    'sites' => ['default' => ['uriFormat' => 'test/{slug', 'template' => 'data']]
                ]
            ]
        ];
    }
}
