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
 */
class GqlResponseFormatter extends JsonResponseFormatter
{
    /**
     * @inheritdoc
     */
    public $contentType = 'application/graphql-response+json';
}
