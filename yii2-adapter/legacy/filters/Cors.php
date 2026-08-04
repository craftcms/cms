<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\filters;

/**
 * Filter for adding CORS headers to site responses and handling OPTIONS requests.
 *
 * @see https://www.yiiframework.com/doc/api/2.0/yii-filters-cors
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 * @deprecated 6.0.0 use Laravel's CORS settings instead. @see https://laravel.com/docs/12.x/routing#cors
 */
class Cors extends \yii\filters\Cors
{
    use SiteFilterTrait;
}
