<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql;

use Craft;
use craft\config\GeneralConfig;
use craft\elements\Asset;
use craft\gql\base\Directive;
use craft\gql\directives\FormatDateTime;
use craft\gql\directives\Markdown;
use craft\gql\directives\Money;
use craft\gql\directives\Transform;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\elements\Asset as GqlAssetType;
use craft\gql\types\elements\Entry as GqlEntryType;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\services\Config;
use craft\test\mockclasses\elements\ExampleElement;
use craft\test\mockclasses\gql\MockDirective;
use craft\test\TestCase;
use DateTime;
use DateTimeZone;
use GraphQL\Type\Definition\ResolveInfo;

class DirectiveTest extends TestCase
{
    protected function _before(): void
    {
    }

    protected function _after(): void
    {
    }

    /**
     * Test directives
     *
     * @dataProvider directiveDataProvider
     * @param mixed $in input
     * @param mixed $directiveClass
     * @param array $directives an array of directive data as expected by GQL
     * @param string $result expected result
     */
    public function testDirectivesBeingApplied(mixed $in, mixed $directiveClass, array $directives, string $result): void
    {
        $this->_registerDirective($directiveClass);

        /** @var GqlEntryType $type */
        $type = $this->make(GqlEntryType::class);
        $element = new ExampleElement();
        $element->someField = $in;

        $fieldNodes = new \ArrayObject([Json::decode('{"directives":[' . implode(',', $directives) . ']}', false)]);

        $resolveInfo = $this->make(ResolveInfo::class, [
            'fieldName' => 'someField',
            'fieldNodes' => $fieldNodes,
        ]);

        self::assertEquals($result, $type->resolveWithDirectives($element, [], null, $resolveInfo));
    }

    /**
     * Test if transform is only correctly applied to URL.
     */
    public function testTransformOnlyUrl(): void
    {
        /** @var Asset $asset */
        $asset = $this->make(Asset::class, ['filename' => StringHelper::randomString() . '.jpg']);

        /** @var GqlAssetType $type */
        $type = $this->make(GqlAssetType::class);

        $fieldNodes = new \ArrayObject([Json::decode('{"directives":[' . $this->_buildDirective(Transform::class, ['width' => 200]) . ']}', false)]);

        $resolveInfo = $this->make(ResolveInfo::class, [
            'fieldName' => 'filename',
            'fieldNodes' => $fieldNodes,
        ]);

        self::assertEquals($asset->getFilename(), $type->resolveWithDirectives($asset, [], null, $resolveInfo));
    }

    public function directiveDataProvider(): array
    {
        $mockDirective = MockDirective::class;
        $formatDateTime = FormatDateTime::class;
        $markDownDirective = Markdown::class;
        $moneyDirective = Money::class;

        $dateTime = new DateTime('now');

        $dateTimeParameters = [
            ['format' => 'Y-m-d H:i:s', 'timezone' => 'America/New_York'],
            ['format' => DateTime::ATOM, 'timezone' => 'America/New_York'],
            ['format' => DateTime::COOKIE, 'timezone' => 'America/New_York'],
            ['format' => DateTime::COOKIE, 'timezone' => 'America/New_York'],
        ];

        $money = \Money\Money::USD(123456);

        $moneyParameters = [
            ['format' => Money::FORMAT_NUMBER],
            ['format' => Money::FORMAT_NUMBER, 'locale' => 'nl'],
            ['format' => Money::FORMAT_DECIMAL],
            ['format' => Money::FORMAT_STRING],
            ['format' => Money::FORMAT_AMOUNT],
        ];

        return [
            // Mock directive
            ['TestString', $mockDirective, [$this->_buildDirective($mockDirective, ['prefix' => 'Foo'])], 'FooTestString'],
            ['TestString', $mockDirective, [$this->_buildDirective($mockDirective, ['prefix' => 'Bar']), $this->_buildDirective($mockDirective, ['prefix' => 'Foo'])], 'FooBarTestString'],

            // format date time (not as handy as for transform parameters, but still better than duplicating formats.
            [$dateTime, $formatDateTime, [$this->_buildDirective($formatDateTime, $dateTimeParameters[0])], $dateTime->setTimezone(new DateTimeZone($dateTimeParameters[0]['timezone']))->format($dateTimeParameters[0]['format'])],
            [$dateTime, $formatDateTime, [$this->_buildDirective($formatDateTime, $dateTimeParameters[1])], $dateTime->setTimezone(new DateTimeZone($dateTimeParameters[1]['timezone']))->format($dateTimeParameters[1]['format'])],
            [$dateTime, $formatDateTime, [$this->_buildDirective($formatDateTime, $dateTimeParameters[2])], $dateTime->setTimezone(new DateTimeZone($dateTimeParameters[2]['timezone']))->format($dateTimeParameters[2]['format'])],
            [$dateTime, $formatDateTime, [$this->_buildDirective($formatDateTime, $dateTimeParameters[3])], $dateTime->setTimezone(new DateTimeZone($dateTimeParameters[3]['timezone']))->format($dateTimeParameters[3]['format'])],
            ['what time is it?', $formatDateTime, [$this->_buildDirective($formatDateTime, $dateTimeParameters[2])], 'what time is it?'],

            // Markdown
            ['Some *string*', $markDownDirective, [$this->_buildDirective($markDownDirective, [])], "<p>Some <em>string</em></p>\n"],

            // Money
            'money-number' => [$money, $moneyDirective, [$this->_buildDirective($moneyDirective, $moneyParameters[0])], '1,234.56'],
            'money-number-locale' => [$money, $moneyDirective, [$this->_buildDirective($moneyDirective, $moneyParameters[1])], '1.234,56'],
            'money-decimal' => [$money, $moneyDirective, [$this->_buildDirective($moneyDirective, $moneyParameters[2])], '1234.56'],
            'money-string' => [$money, $moneyDirective, [$this->_buildDirective($moneyDirective, $moneyParameters[3])], '$1,234.56'],
            'money-amount' => [$money, $moneyDirective, [$this->_buildDirective($moneyDirective, $moneyParameters[4])], '123456'],
        ];
    }

    public function assetTransformDirectiveDataProvider(): array
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
            [$assetTransform, [$this->_buildDirective($assetTransform, $transformParameters[0])], $transformParameters[0]],
            [$assetTransform, [$this->_buildDirective($assetTransform, $transformParameters[1])], $transformParameters[1]],
            [$assetTransform, [$this->_buildDirective($assetTransform, $transformParameters[2])], $transformParameters[2]],
            [$assetTransform, [$this->_buildDirective($assetTransform, $transformParameters[3])], $transformParameters[3]],
        ];
    }

    /**
     * Build the JSON string to be used as a directive object
     *
     * @param string $className
     * @phpstan-param class-string<Directive> $className
     * @param array $arguments
     * @return string
     */
    private function _buildDirective(string $className, array $arguments = []): string
    {
        $directiveTemplate = '{"name": {"value": "%s"}, "arguments": [%s]}';
        $argumentTemplate = '{"name": {"value":"%s"}, "value": {"value": "%s"}}';

        $argumentList = [];
        foreach ($arguments as $key => $value) {
            $argumentList[] = sprintf($argumentTemplate, $key, addslashes($value));
        }

        /** @var string|Directive $className */
        return sprintf($directiveTemplate, $className::name(), implode(', ', $argumentList));
    }

    /**
     * Register a directive by class name.
     *
     * @param string $className
     * @phpstan-param class-string<Directive> $className
     */
    private function _registerDirective(string $className)
    {
        // Make sure the mock directive is available in the entity registry
        /** @var string|Directive $className */
        $directiveName = $className::name();

        Craft::$app->set('config', $this->make(Config::class, [
            'getGeneral' => $this->make(GeneralConfig::class, [
                'gqlTypePrefix' => 'test',
            ]),
        ]));

        if (!GqlEntityRegistry::getEntity($directiveName)) {
            GqlEntityRegistry::createEntity($directiveName, $className::create());
        }
    }
}
