<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use craft\web\Controller;
use craft\web\UploadedFile;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use yii\web\BadRequestHttpException;
use yii\web\Response;

use function CraftCms\Cms\t;

/**
 * The AssetsController class is a controller that handles various actions related to asset tasks, such as uploading
 * files and creating/deleting/renaming files and folders.
 *
 * Note: Most actions have been ported to Laravel controllers in `CraftCms\Cms\Http\Controllers\Assets\`.
 * Only the deprecated {@link actionSaveAsset()} remains here for backwards compatibility.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 * @deprecated 6.0.0
 */
class AssetsController extends Controller
{
    use AssetsControllerTrait;

    /**
     * Saves an asset.
     *
     * @return Response|null
     *
     * @throws BadRequestHttpException
     *
     * @deprecated in 4.0.0. Use `assets/upload` for uploads, or save elements directly.
     */
    public function actionSaveAsset(): ?Response
    {
        if (UploadedFile::getInstanceByName('assets-upload') !== null) {
            Deprecator::log(__METHOD__, 'Uploading new files via `assets/save-asset` has been deprecated. Use `assets/upload` instead.');

            return $this->runAction('upload');
        }

        $assetId = $this->request->getBodyParam('sourceId') ?? $this->request->getRequiredParam('assetId');
        $siteId = $this->request->getBodyParam('siteId');
        $assetVariable = $this->request->getValidatedBodyParam('assetVariable') ?? 'asset';

        /** @var Asset|null $asset */
        $asset = Asset::find()
            ->id($assetId)
            ->siteId($siteId)
            ->one();

        if ($asset === null) {
            throw new BadRequestHttpException("Invalid asset ID: $assetId");
        }

        $this->requireVolumePermissionByAsset('saveAssets', $asset);
        $this->requirePeerVolumePermissionByAsset('savePeerAssets', $asset);

        if (Sites::isMultiSite()) {
            // Make sure they have access to this site
            $this->requirePermission('editSite:' . $asset->getSite()->uid);
        }

        $asset->title = $this->request->getParam('title') ?? $asset->title;
        $asset->newFilename = $this->request->getParam('filename');

        $fieldsLocation = $this->request->getParam('fieldsLocation') ?? 'fields';
        $asset->setFieldValuesFromRequest($fieldsLocation);

        // Save the asset
        $asset->ruleset->useScenario(ElementRules::SCENARIO_LIVE);

        if (!Elements::saveElement($asset)) {
            return $this->asModelFailure(
                $asset,
                mb_ucfirst(t('Couldn’t save {type}.', [
                    'type' => Asset::lowerDisplayName(),
                ])),
                $assetVariable
            );
        }

        return $this->asModelSuccess(
            $asset,
            t('{type} saved.', [
                'type' => Asset::displayName(),
            ]),
            data: [
                'id' => $asset->id,
                'title' => $asset->title,
                'url' => $asset->getUrl(),
                'cpEditUrl' => $asset->getCpEditUrl(),
            ],
        );
    }
}
