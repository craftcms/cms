<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\graphiql;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * GraphiQL asset bundle.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class GraphiqlAsset extends AssetBundle
{
    /** @inheritdoc */
    public $depends = [
        CpAsset::class,
    ];

    /** @inheritdoc */
    public $sourcePath = __DIR__ . '/dist';

    /** @inheritdoc */
    public $js = [
        'graphiql.js',
    ];

    /** @inheritdoc */
    public $css = [
        'graphiql.css',
    ];
}
