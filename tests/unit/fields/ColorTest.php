<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\fields;

use Craft;
use craft\fields\Color;
use craft\fields\data\ColorData;
use craft\test\TestCase;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;

/**
 * Unit tests for the Color field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class ColorTest extends TestCase
{
    public function testGraphqlModeDefaults(): void
    {
        self::assertTrue((new Color())->fullGraphqlData);
        // existing fields keep returning hex values
        self::assertFalse((new Color(['id' => 1]))->fullGraphqlData);
        // the GraphQL Mode setting’s posted value
        $fields = Craft::$app->getFields();
        self::assertTrue($fields->createField(['type' => Color::class, 'id' => 1, 'graphqlMode' => 'full'])->fullGraphqlData);
        self::assertFalse($fields->createField(['type' => Color::class, 'graphqlMode' => 'hex'])->fullGraphqlData);
    }

    public function testHexOnlyGraphqlType(): void
    {
        $type = (new Color(['fullGraphqlData' => false]))->getContentGqlType();
        self::assertSame(Type::string(), is_array($type) ? $type['type'] : $type);
    }

    public function testFullGraphqlData(): void
    {
        $schema = new Schema([
            'query' => new ObjectType([
                'name' => 'Query',
                'fields' => [
                    'color' => [
                        'type' => (new Color())->getContentGqlType(),
                        'resolve' => fn() => new ColorData('#0000ff80', ['label' => 'Blue']),
                    ],
                ],
            ]),
        ]);

        $result = GraphQL::executeQuery($schema, <<<GQL
{
  color {
    hex
    opaqueHex: hex(withAlpha: false)
    label
    red
    blue
    alpha
    rgba
    rgbChannels
    hsla
    oklch
    contrastColor
    contrastRatio(with: "#ffffff")
  }
}
GQL)->toArray();

        self::assertArrayNotHasKey('errors', $result);
        $color = $result['data']['color'];
        self::assertSame('#0000ff80', $color['hex']);
        self::assertSame('#0000ff', $color['opaqueHex']);
        self::assertSame('Blue', $color['label']);
        self::assertSame(0, $color['red']);
        self::assertSame(255, $color['blue']);
        self::assertSame(0.5, $color['alpha']);
        self::assertSame('rgba(0,0,255,0.5)', $color['rgba']);
        self::assertSame('0 0 255', $color['rgbChannels']);
        self::assertSame('hsla(240,100%,50%,0.5)', $color['hsla']);
        self::assertSame('oklch(45.2% 0.3132 264.05 / 0.5)', $color['oklch']);
        self::assertSame('#ffffff', $color['contrastColor']);
        self::assertEqualsWithDelta(8.59, $color['contrastRatio'], 0.005);
    }
}
