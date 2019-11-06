<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\graphiql;

use craft\web\AssetBundle;

/**
 * VendorAsset asset bundle.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class VendorAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@lib';

        $this->js = [
            'es6-promise/es6-promise.min.js',
            'fetch/fetch.js',
            'react/react.production.min.js',
            'react-dom/react-dom.production.min.js',
            'graphiql/js/graphiql.min.js',
        ];

        $this->css = [
            'graphiql/css/graphiql.css',
        ];

        parent::init();
    }
}
