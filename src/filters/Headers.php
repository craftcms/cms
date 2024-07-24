<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\filters;

use Craft;

/**
 * Filter for adding arbitrary headers to site responses and handling OPTIONS requests.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.11.0
 */
class Headers extends \yii\base\ActionFilter
{
    use SiteFilterTrait;

    public array $headers = [];

    public function beforeAction($action): bool
    {
        foreach ($this->headers as $name => $value) {
            Craft::$app->getResponse()->getHeaders()->set($name, $value);
        }

        return true;
    }
}
