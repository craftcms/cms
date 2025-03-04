<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\filters;

use Craft;
use yii\base\ActionFilter;

/**
 * Filter for adding arbitrary headers to site responses and handling OPTIONS requests.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class Headers extends ActionFilter
{
    use SiteFilterTrait;

    /**
     * @var array<string,string|string[]> The headers that should be set on responses.
     */
    public array $headers = [];

    public function beforeAction($action): bool
    {
        if (!empty($this->headers)) {
            $responseHeaders = Craft::$app->getResponse()->getHeaders();
            foreach ($this->headers as $name => $value) {
                $responseHeaders->set($name, $value);
            }
        }

        return true;
    }
}
