<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use craft\helpers\Json;
use yii\base\Component;
use yii\web\Response as YiiResponse;
use yii\web\ResponseFormatterInterface;

/**
 * CsvResponseFormatter formats the given data into CSV response content.
 *
 * It is used by [[YiiResponse]] to format response data.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class CsvResponseFormatter extends Component implements ResponseFormatterInterface
{
    /**
     * @var string the Content-Type header for the response
     */
    public $contentType = 'text/csv';

    /**
     * @var bool whether the response data should include a header row
     */
    public $includeHeaderRow = true;

    /**
     * @var string[] the header row values. The array keys of first result in
     * [[YiiResponse::$data]] will be used by default.
     */
    public $headers;

    /**
     * @var string the field delimiter (one character only)
     */
    public $delimiter = ',';

    /**
     * @var string the field enclosure (one character only)
     */
    public $enclosure = '"';

    /**
     * @var string the escape character (one character only)
     */
    public $escapeChar = "\\";

    /**
     * Formats the specified response.
     *
     * @param YiiResponse $response the response to be formatted.
     */
    public function format($response)
    {
        if (stripos($this->contentType, 'charset') === false) {
            $this->contentType .= '; charset=' . $response->charset;
        }
        $response->getHeaders()->set('Content-Type', $this->contentType);

        $data = is_array($response->data) ? $response->data : [];
        if (empty($data) && empty($this->headers)) {
            $response->content = '';
            return;
        }

        $file = tempnam(sys_get_temp_dir(), 'csv');
        $fp = fopen($file, 'wb');

        if ($this->includeHeaderRow) {
            $headers = $this->headers ?? array_keys(reset($data));
            fputcsv($fp, $headers, ',');
        }

        foreach ($data as $row) {
            foreach ($row as &$field) {
                if (is_scalar($field)) {
                    $field = (string)$field;
                } else {
                    $field = Json::encode($field);
                }
            }
            unset($field);
            fputcsv($fp, $row, $this->delimiter, $this->enclosure, $this->escapeChar);
        }

        fclose($fp);
        $response->content = file_get_contents($file);
        unlink($file);
    }
}
