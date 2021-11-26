<?php
declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use craft\elements\Asset;
use craft\errors\ImageTransformException;
use craft\helpers\App;
use craft\models\ImageTransformIndex;

/**
 * DefaultDriver transforms the images using the Imagine library.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class ImageTransformDriver implements ImageTransformDriverInterface
{
    /**
     * Generate the transform based on an index.
     *
     * @param ImageTransformIndex $index
     * @return bool
     */
    abstract protected function generateTransform(ImageTransformIndex $index): bool;

    /**
     * Get a transform URL by the transform index model.
     *
     * @param Asset $asset
     * @param ImageTransformIndex $index
     * @return string
     * @throws ImageTransformException If there was an error generating the transform.
     */
    public function ensureTransformUrlByIndexModel(Asset $asset, ImageTransformIndex $index): string
    {
        $assetTransformService = Craft::$app->getImageTransforms();

        // Make sure we're not in the middle of working on this transform from a separate request
        if ($index->inProgress) {
            for ($safety = 0; $safety < 100; $safety++) {

                if ($index->error) {
                    throw new ImageTransformException(Craft::t('app',
                        'Failed to generate transform with id of {id}.',
                        ['id' => $index->id]));
                }

                // Wait a second!
                sleep(1);
                App::maxPowerCaptain();

                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                $index = $assetTransformService->getTransformIndexModelById($index->id);

                // Is it being worked on right now?
                if ($index->inProgress) {
                    // Make sure it hasn't been working for more than 30 seconds. Otherwise give up on the other request.
                    $time = new \DateTime();

                    if ($time->getTimestamp() - $index->dateUpdated->getTimestamp() < 30) {
                        continue;
                    }

                    $assetTransformService->storeTransformIndexData($index);
                    break;
                }

                // Must be done now!
                break;
            }
        }

        if (!$index->fileExists) {
            // Mark the transform as in progress
            $index->inProgress = true;
            $assetTransformService->storeTransformIndexData($index);

            // Generate the transform
            try {
                if ($this->generateTransform($index)) {
                    // Update the index
                    $index->inProgress = false;
                    $index->fileExists = true;
                } else {
                    $index->inProgress = false;
                    $index->fileExists = false;
                    $index->error = true;
                }

                $assetTransformService->storeTransformIndexData($index);
            } catch (\Exception $e) {
                $index->inProgress = false;
                $index->fileExists = false;
                $index->error = true;
                $assetTransformService->storeTransformIndexData($index);
                Craft::$app->getErrorHandler()->logException($e);

                throw new ImageTransformException(Craft::t('app',
                    'Failed to generate transform with id of {id}.',
                    ['id' => $index->id]));
            }
        }

        return $this->getTransformUrl($asset, $index);
    }
}
