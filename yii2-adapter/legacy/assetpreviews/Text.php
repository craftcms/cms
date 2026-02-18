<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\assetpreviews;

use craft\base\AssetPreviewHandler;
use craft\helpers\FileHelper;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Support\Html;
use function CraftCms\Cms\template;

/**
 * Provides functionality to preview text files as HTML
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class Text extends AssetPreviewHandler
{
    /**
     * @inheritdoc
     */
    public function getPreviewHtml(array $variables = []): string
    {
        $localCopy = $this->asset->getCopyOfFile();
        $contents = Html::encode(file_get_contents($localCopy));
        FileHelper::unlink($localCopy);
        $language = $this->asset->kind === Asset::KIND_HTML ? 'markup' : $this->asset->kind;

        return template('assets/_previews/text',
            array_merge([
                'asset' => $this->asset,
                'language' => $language,
                'contents' => $contents,
            ], $variables)
        );
    }
}
