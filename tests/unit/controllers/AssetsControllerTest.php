<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\controllers;

use Craft;
use craft\controllers\AssetsController;
use craft\elements\Asset;
use craft\elements\User;
use craft\test\TestCase;
use crafttests\fixtures\AssetFixture;
use UnitTester;

/**
 * Unit tests for AssetsController
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.11.6
 */
class AssetsControllerTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    private ?string $originalRequestMethod = null;

    public function _fixtures(): array
    {
        return [
            'assets' => [
                'class' => AssetFixture::class,
            ],
        ];
    }

    public function testMoveAssetReplacesConflictingAssetInAnotherVolume(): void
    {
        $assetsService = Craft::$app->getAssets();
        $asset = Asset::find()->filename('shinybrad.png')->one();
        $conflictingAsset = Asset::find()->filename('shinybrad2.png')->one();
        $targetFolder = $assetsService->getFolderById(1001);

        // Give the asset in the other volume the same filename
        self::assertTrue($assetsService->moveAsset($conflictingAsset, $targetFolder, 'shinybrad.png'));

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = Craft::$app->getRequest();
        $request->setBodyParams([
            'assetId' => $asset->id,
            'folderId' => $targetFolder->id,
            'force' => true,
        ]);
        $request->getHeaders()->set('Accept', 'application/json');

        $controller = new AssetsController('assets', Craft::$app);
        $response = $controller->actionMoveAsset();

        self::assertSame(200, $response->getStatusCode(), (string)json_encode($response->data));

        $movedAsset = Asset::find()->id($asset->id)->one();
        self::assertSame($targetFolder->id, $movedAsset->folderId);
        self::assertSame(1003, $movedAsset->volumeId);
        self::assertNull(Asset::find()->id($conflictingAsset->id)->one(), 'The conflicting asset was replaced.');
    }

    /**
     * @see https://github.com/craftcms/cms/issues/20601
     */
    public function testSaveImageClampsCropToImageBounds(): void
    {
        // 460×460, shown at 100×100 in the editor
        $asset = Asset::find()->filename('shinybrad.png')->one();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = Craft::$app->getRequest();
        $request->setBodyParams([
            'assetId' => $asset->id,
            'viewportRotation' => 0,
            'imageRotation' => 0,
            'replace' => false,
            // The left half of the image, with the crop box 0.5px past its left edge (2.3px at full size)
            'cropData' => [
                'offsetX' => -25.5,
                'offsetY' => 0,
                'width' => 50,
                'height' => 100,
            ],
            'imageDimensions' => [
                'width' => 100,
                'height' => 100,
            ],
        ]);
        $request->getHeaders()->set('Accept', 'application/json');

        $controller = new AssetsController('assets', Craft::$app);
        $response = $controller->actionSaveImage();

        self::assertSame(200, $response->getStatusCode(), (string)json_encode($response->data));

        // The crop keeps its right edge (49.5px on screen = 227.7px at full size) and starts at the image's left edge
        $newAsset = Asset::find()->id($response->data['newAssetId'])->one();
        self::assertSame(227, $newAsset->width);
        self::assertSame(460, $newAsset->height);
    }

    protected function _before(): void
    {
        parent::_before();

        $this->originalRequestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
    }

    protected function _after(): void
    {
        Craft::$app->getUser()->logout(false);

        if ($this->originalRequestMethod === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->originalRequestMethod;
        }

        parent::_after();
    }
}
