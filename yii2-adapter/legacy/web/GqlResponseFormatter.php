<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use yii\web\JsonResponseFormatter;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.17.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Http\Responses\GqlResponse} instead.
 */
class GqlResponseFormatter extends JsonResponseFormatter
{
    /**
     * @inheritdoc
     */
    public $contentType = 'application/graphql-response+json';
}
