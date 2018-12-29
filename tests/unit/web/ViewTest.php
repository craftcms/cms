<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
namespace craftunit\web;


use craft\test\TestCase;
use craft\web\View;
use craftunit\fixtures\SitesFixture;

/**
 * Unit tests for the Url Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class ViewTest extends TestCase
{
    public function _fixtures()
    {
        return [
            'sites' => [
                'class' => SitesFixture::class
            ]
        ];
    }

    /**
     * @var \UnitTester
     */
    protected $tester;


    /**
     * @param $result
     * @param $input
     * @dataProvider normalizeObjectTemplateData
     */
    public function testNormalizeObjectTemplate($result, $input)
    {
        $this->assertSame($result, \Craft::$app->getView()->normalizeObjectTemplate($input));
    }
    public function normalizeObjectTemplateData()
    {
        return [
            ['{{ object.titleWithHyphens|replace({\'-\': \'!\'}) }}', '{{ object.titleWithHyphens|replace({\'-\': \'!\'}) }}'],
            ['{{ (_variables.foo ?? object.foo)|raw }}', '{foo}'],
            ['{{ (_variables.foo ?? object.foo).bar|raw }}', '{foo.bar}'],
            ['{foo : \'bar\'}', '{foo : \'bar\'}'],
            ['{{foo}}', '{{foo}}'],
            ['{% foo %}', '{% foo %}'],
            ['{{ (_variables.foo ?? object.foo).fn({bar: baz})|raw }}', '{foo.fn({bar: baz})}'],
            ['{{ (_variables.foo ?? object.foo).fn({bar: {baz: 1}})|raw }}', '{foo.fn({bar: {baz: 1}})}'],
            ['{{ (_variables.foo ?? object.foo).fn(\'bar:baz\')|raw }}', '{foo.fn(\'bar:baz\')}'],
            ['{{ (_variables.foo ?? object.foo).fn({\'bar\': baz})|raw }}', '{foo.fn({\'bar\': baz})}']
        ];
    }

    public function testDoesTemplateExistWithCustomSite()
    {
        // Ensure that the current site is the one with the testSite3 handle
        \Craft::$app->getSites()->setCurrentSite(\Craft::$app->getSites()->getSiteByHandle('testSite3'));
        \Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_SITE);

        $this->assertSame(
            \Craft::getAlias('@craftunittemplates/testSite3/craft.twig'),
            \Craft::$app->getView()->resolveTemplate('craft')
        );
    }
    /**
     * @param $result
     * @param $templatePath
     * @dataProvider doesTemplateExistData
     */
    public function testDoesTemplateExistsInSite($result, $templatePath)
    {
        \Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_SITE);

        $doesIt = \Craft::$app->getView()->resolveTemplate($templatePath);

        if ($result === false) {
            $this->assertFalse($doesIt);
        } else {
            $this->assertSame(\Craft::getAlias($result), $doesIt);
        }
    }

    public function doesTemplateExistData()
    {
        return [
            ['@craftunittemplates/index.html', ''],
            ['@craftunittemplates/template.twig', 'template'],
            [false, 'doesntExist'],
            [false, '@craftunittemplates/index.html'],
            ['@craftunittemplates/testSite3/index.twig', 'testSite3/index.twig'],
            ['@craftunittemplates/testSite3/index.twig', 'testSite3/'],
        ];
    }
    
    // Helpers
    // =========================================================================

    private function resolveTemplate()
    {
        return $this->invokeMethod(\Craft::$app->getView(), '_resolveTemplate');
    }

}
