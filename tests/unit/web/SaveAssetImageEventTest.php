<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web;

use craft\elements\Asset;
use craft\events\SaveAssetImageEvent;
use craft\test\TestCase;

/**
 * Unit tests for the save asset image event.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.19.0
 */
class SaveAssetImageEventTest extends TestCase
{
    public function testEventStoresImageEditorSaveState(): void
    {
        $asset = new Asset();
        $event = new SaveAssetImageEvent([
            'asset' => $asset,
            'replace' => true,
            'viewportRotation' => 90,
            'imageRotation' => 1.5,
            'cropData' => [
                'offsetX' => 10,
                'offsetY' => -5,
                'height' => 300,
                'width' => 400,
            ],
            'focalPoint' => [
                'offsetX' => 7,
                'offsetY' => 9,
                'imageDimensions' => [
                    'width' => 800,
                    'height' => 600,
                ],
            ],
            'imageDimensions' => [
                'width' => 800,
                'height' => 600,
            ],
            'flipData' => [
                'x' => true,
                'y' => false,
            ],
            'zoom' => 1.25,
            'assetId' => 42,
        ]);

        self::assertSame($asset, $event->asset);
        self::assertTrue($event->replace);
        self::assertSame(90, $event->viewportRotation);
        self::assertSame(1.5, $event->imageRotation);
        self::assertSame(400, $event->cropData['width']);
        self::assertSame(7, $event->focalPoint['offsetX']);
        self::assertSame(800, $event->imageDimensions['width']);
        self::assertTrue($event->flipData['x']);
        self::assertSame(1.25, $event->zoom);
        self::assertSame(42, $event->assetId);
    }
}
