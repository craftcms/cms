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


    /**
     * @see testDoesTemplateExistsInSite
     * @param $result
     * @param $input
     * @dataProvider privateResolveTemplateData
     */
    public function testPrivateResolveTemplate($result, $basePath, $name, $templateExtensions = null, $viewTemplateNameExtensions = null)
    {
        // If the data wants to set something custom? Set it as a prop.
        if ($templateExtensions !== null) {
            $this->setInaccessibleProperty(\Craft::$app->getView(), '_defaultTemplateExtensions', $templateExtensions);
        }

        // Same with index names
        if ($viewTemplateNameExtensions !== null) {
            $this->setInaccessibleProperty(\Craft::$app->getView(), '_indexTemplateFilenames', $viewTemplateNameExtensions);
        }


        $resolved = $this->resolveTemplate(\Craft::getAlias($basePath), $name);
        $this->assertSame(\Craft::getAlias($result), $resolved);
    }
    public function privateResolveTemplateData()
    {
        return [
            ['@craftunittemplates/template.twig', '@craftunittemplates', 'template'],
            ['@craftunittemplates/index.html', '@craftunittemplates', 'index'],
            ['@craftunittemplates/doubleindex/index.html', '@craftunittemplates/doubleindex', 'index'],

            // Index is found by default
            ['@craftunittemplates/index.html', '@craftunittemplates', ''],

            // Assert that registering custom extensions works.
            ['@craftunittemplates/dotxml.xml', '@craftunittemplates', 'dotxml', ['xml']],
            [null, '@craftunittemplates', 'dotxml'],
            ['@craftunittemplates/dotxml.xml', '@craftunittemplates', 'dotxml.xml',],

            // Allow change in index names
            ['@craftunittemplates/template.twig', '@craftunittemplates', '', null, ['template']],
        ];
    }


    // Helpers
    // =========================================================================

    private function resolveTemplate($basePath, $name)
    {
        return $this->invokeMethod(\Craft::$app->getView(), '_resolveTemplate', [$basePath, $name]);
    }

}
