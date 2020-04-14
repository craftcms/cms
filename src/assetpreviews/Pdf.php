<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\assetpreviews;

use craft\base\AssetPreviewHandler;
use craft\helpers\Html;
use yii\base\NotSupportedException;

/**
 * Provides functionality to preview PDFs
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class Pdf extends AssetPreviewHandler
{
    /**
     * @inheritdoc
     */
    public function getPreviewHtml(): string
    {
        $url = $this->asset->getUrl();

        if ($url === null) {
            throw new NotSupportedException('Preview not supported.');
        }

        return Html::tag('iframe', '', [
            'width' => '100%',
            'height' => '100%',
            'src' => $url,
        ]);
    }
}
