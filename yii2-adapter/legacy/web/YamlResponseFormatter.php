<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Symfony\Component\Yaml\Yaml;
use yii\base\Component;
use yii\web\Response as YiiResponse;
use yii\web\ResponseFormatterInterface;

/**
 * YamlResponseFormatter formats the given data into YAML response content.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.9.0
 */
class YamlResponseFormatter extends Component implements ResponseFormatterInterface
{
    /**
     * Formats the specified response.
     *
     * @param YiiResponse $response the response to be formatted.
     */
    public function format($response): void
    {
        $response->getHeaders()->set('Content-Type', "application/x-yaml; charset=$response->charset");

        $data = is_iterable($response->data) ? $response->data : [];
        if (empty($data) && empty($this->headers)) {
            $response->content = '';
            return;
        }

        $response->content = Yaml::dump($data, 20, 2);
    }
}
