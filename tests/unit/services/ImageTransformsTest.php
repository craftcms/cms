<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use craft\helpers\DateTimeHelper;
use craft\helpers\ImageTransforms;
use craft\models\ImageTransform;
use craft\test\TestCase;

class ImageTransformsTest extends TestCase
{
    /**
     * Makes sure that extending transform correctly updates it.
     *
     * @dataProvider extendTransformDataProvider
     * @param ImageTransform $transform
     * @param array $parameters
     * @param array $resultCheck
     */
    public function testExtendTransform(ImageTransform $transform, array $parameters, array $resultCheck): void
    {
        $extendedTransform = ImageTransforms::extendTransform($transform, $parameters);

        foreach ($resultCheck as $property => $value) {
            self::assertSame($value, $extendedTransform->{$property});
        }
    }

    public function testExtendingTransformReturnsNewObject(): void
    {
        $transform = new ImageTransform(['width' => 200, 'height' => 200]);
        $extendedTransform = ImageTransforms::extendTransform($transform, ['height' => 300]);
        self::assertNotSame($extendedTransform, $transform);
    }

    public function extendTransformDataProvider(): array
    {
        return [
            [
                new ImageTransform(['width' => 200, 'height' => 200]),
                ['format' => 'jpg'],
                ['width' => 200, 'height' => 200, 'format' => 'jpg'],
            ],
            [
                new ImageTransform(['width' => 200, 'height' => 200]),
                [],
                ['width' => 200, 'height' => 200],
            ],
            [
                new ImageTransform(['width' => 200, 'height' => 200]),
                ['width' => null],
                ['width' => null, 'height' => 200],
            ],
            [
                new ImageTransform(['width' => 200, 'height' => 200, 'handle' => 'square']),
                ['format' => 'jpg', 'handle' => 'rectangle'],
                ['width' => 200, 'height' => 200, 'format' => 'jpg', 'handle' => null],
            ],
            [
                new ImageTransform(['width' => 200, 'height' => 200, 'id' => 88, 'uid' => 100, 'handle' => 'square', 'parameterChangeTime' => DateTimeHelper::currentUTCDateTime()]),
                ['format' => 'jpg'],
                ['width' => 200, 'height' => 200, 'format' => 'jpg', 'handle' => null, 'id' => null, 'uid' => null, 'parameterChangeTime' => null],
            ],
        ];
    }
}
