<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql;

use Codeception\Test\Unit;
use Craft;
use craft\config\GeneralConfig;
use craft\console\Application;
use craft\elements\Asset;
use craft\gql\directives\FormatDateTime;
use craft\gql\directives\Markdown;
use craft\gql\directives\Transform;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\elements\Asset as GqlAssetType;
use craft\gql\types\elements\Entry as GqlEntryType;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\services\Config;
use craft\test\mockclasses\elements\ExampleElement;
use craft\test\mockclasses\gql\MockDirective;
use DateTime;
use GraphQL\Type\Definition\ResolveInfo;

class DirectiveTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    /**
     * Test directives
     *
     * @dataProvider directiveDataProvider
     *
     * @param string $in input string
     * @param array $directives an array of directive data as expected by GQL
     * @param string $result expected result
     */
    public function testDirectivesBeingApplied($in, array $directives, $result)
    {
        /** @var GqlEntryType $type */
        $type = $this->make(GqlEntryType::class);
        $element = new ExampleElement();
        $element->someField = $in;

        $fieldNodes = [Json::decode('{"directives":[' . implode(',', $directives) . ']}', false)];

        $resolveInfo = $this->make(ResolveInfo::class, [
            'fieldName' => 'someField',
            'fieldNodes' => $fieldNodes
        ]);

        $this->assertEquals($result, $type->resolveWithDirectives($element, [], null, $resolveInfo));
    }

    /**
     * Test transform directive
     *
     * @dataProvider assetTransformDirectiveDataProvider
     *
     * @param array $directives an array of directive data as expected by GQL
     * @param array $parameters transform parameters
     * @param boolean $mustNotBeSame Whether the results should differ instead
     */
    public function testTransformDirective(array $directives, $parameters, $mustNotBeSame = false)
    {
        $this->tester->mockMethods(
            Craft::$app,
            'assets',
            [
                'getAssetUrl' => function($asset, $parameters, $generateNow) {
                    $transformed = is_array($parameters) ? implode('-', $parameters) : $parameters;
                    return $transformed . ($generateNow ? ($asset->filename . '-generateNow') : ($asset->filename . 'generateLater'));
                }
            ],
            []
        );

        /** @var Asset $asset */
        $asset = $this->make(Asset::class, ['filename' => StringHelper::randomString() . '.jpg']);

        /** @var GqlAssetType $type */
        $type = $this->make(GqlAssetType::class);

        $fieldNodes = [Json::decode('{"directives":[' . implode(',', $directives) . ']}', false)];

        $resolveInfo = $this->make(ResolveInfo::class, [
            'fieldName' => 'url',
            'fieldNodes' => $fieldNodes
        ]);

        $generateNow = $parameters['immediately'] ?? Craft::$app->getConfig()->general->generateTransformsBeforePageLoad;
        unset($parameters['immediately']);

        // `handle` parameter overrides everything else.
        if (!empty($parameters['handle'])) {
            $parameters = $parameters['handle'];
        }

        if ($mustNotBeSame) {
            $this->assertNotEquals(Craft::$app->getAssets()->getAssetUrl($asset, $parameters, $generateNow), $type->resolveWithDirectives($asset, [], null, $resolveInfo));
        } else {
            $this->assertEquals(Craft::$app->getAssets()->getAssetUrl($asset, $parameters, $generateNow), $type->resolveWithDirectives($asset, [], null, $resolveInfo));
        }
    }

    /**
     * Test if transform is only correctly applied to URL.
     */
    public function testTransformOnlyUrl()
    {
        /** @var Asset $asset */
        $asset = $this->make(Asset::class, ['filename' => StringHelper::randomString() . '.jpg']);

        /** @var GqlAssetType $type */
        $type = $this->make(GqlAssetType::class);

        $fieldNodes = [Json::decode('{"directives":[' . $this->_buildDirective(Transform::class, ['width' => 200]) . ']}', false)];

        $resolveInfo = $this->make(ResolveInfo::class, [
            'fieldName' => 'filename',
            'fieldNodes' => $fieldNodes
        ]);

        $this->assertEquals($asset->filename, $type->resolveWithDirectives($asset, [], null, $resolveInfo));
    }

    public function directiveDataProvider()
    {
        $mockDirective = MockDirective::class;
        $formatDateTime = FormatDateTime::class;
        $markDownDirective = Markdown::class;

        $dateTime = new DateTime('now');

        $dateTimeParameters = [
            ['format' => 'Y-m-d H:i:s', 'timezone' => 'America/New_York'],
            ['format' => DateTime::ATOM, 'timezone' => 'America/New_York'],
            ['format' => DateTime::COOKIE, 'timezone' => 'America/New_York'],
            ['format' => DateTime::COOKIE, 'timezone' => 'America/New_York'],
        ];

        return [
            // Mock directive
            ['TestString', [$this->_buildDirective($mockDirective, ['prefix' => 'Foo'])], 'FooTestString'],
            ['TestString', [$this->_buildDirective($mockDirective, ['prefix' => 'Bar']), $this->_buildDirective($mockDirective, ['prefix' => 'Foo'])], 'FooBarTestString'],

            // format date time (not as handy as for transform parameters, but still better than duplicating formats.
            [$dateTime, [$this->_buildDirective($formatDateTime, $dateTimeParameters[0])], $dateTime->setTimezone(new \DateTimeZone($dateTimeParameters[0]['timezone']))->format($dateTimeParameters[0]['format'])],
            [$dateTime, [$this->_buildDirective($formatDateTime, $dateTimeParameters[1])], $dateTime->setTimezone(new \DateTimeZone($dateTimeParameters[1]['timezone']))->format($dateTimeParameters[1]['format'])],
            [$dateTime, [$this->_buildDirective($formatDateTime, $dateTimeParameters[2])], $dateTime->setTimezone(new \DateTimeZone($dateTimeParameters[2]['timezone']))->format($dateTimeParameters[2]['format'])],
            [$dateTime, [$this->_buildDirective($formatDateTime, $dateTimeParameters[3])], $dateTime->setTimezone(new \DateTimeZone($dateTimeParameters[3]['timezone']))->format($dateTimeParameters[3]['format'])],
            ['what time is it?', [$this->_buildDirective($formatDateTime, $dateTimeParameters[2])], 'what time is it?'],

            // Markdown
            ["Some *string*", [$this->_buildDirective($markDownDirective, [])], "<p>Some <em>string</em></p>\n"],
        ];
    }

    public function assetTransformDirectiveDataProvider()
    {
        $assetTransform = Transform::class;

        $transformParameters = [
            ['handle' => 'anExampleTransform', 'immediately' => false],
            ['handle' => 'anExampleTransform', 'immediately' => true],
            ['mode' => 'fit', 'width' => 30, 'height' => 40, 'format' => 'png', 'position' => 'top-left', 'interlace' => 'line', 'quality' => 5, 'immediately' => true],
            ['mode' => 'fit', 'width' => 30, 'height' => 40, 'format' => 'png', 'position' => 'top-left', 'interlace' => 'line', 'quality' => 5, 'immediately' => false],
        ];

        // asset transform
        return [
            [[$this->_buildDirective($assetTransform, $transformParameters[0])], $transformParameters[0]],
            [[$this->_buildDirective($assetTransform, $transformParameters[1])], $transformParameters[1]],
            [[$this->_buildDirective($assetTransform, $transformParameters[2])], $transformParameters[2]],
            [[$this->_buildDirective($assetTransform, $transformParameters[3])], $transformParameters[3]],
        ];
    }

    /**
     * Build the JSON string to be used as a directive object
     *
     * @param string $className
     * @param array $arguments
     * @return string
     */
    private function _buildDirective(string $className, array $arguments = [])
    {
        $this->_registerDirective($className);

        $directiveTemplate = '{"name": {"value": "%s"}, "arguments": [%s]}';
        $argumentTemplate = '{"name": {"value":"%s"}, "value": {"value": "%s"}}';

        $argumentList = [];
        foreach ($arguments as $key => $value) {
            $argumentList[] = sprintf($argumentTemplate, $key, addslashes($value));
        }

        return sprintf($directiveTemplate, $className::name(), implode(', ', $argumentList));
    }

    /**
     * Register a directive by class name.
     *
     * @param $className
     */
    private function _registerDirective($className)
    {
        // Make sure the mock directive is available in the entity registry
        $directiveName = $className::name();

        Craft::$app = $this->make(Application::class, [
            'getConfig' => $this->make(Config::class, [
                'getGeneral' => $this->make(GeneralConfig::class, [
                    'gqlTypePrefix' => 'test'
                ])
            ])
        ]);

        if (!GqlEntityRegistry::getEntity($directiveName)) {
            GqlEntityRegistry::createEntity($directiveName, $className::create());
        }
    }
}
