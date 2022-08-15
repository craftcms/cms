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
    public string $contentType = 'text/csv';

    /**
     * @var bool whether the response data should include a header row
     */
    public bool $includeHeaderRow = true;

    /**
     * @var string[] the header row values. The array keys of first result in
     * [[YiiResponse::$data]] will be used by default.
     */
    public array $headers;

    /**
     * @var string the field delimiter (one character only)
     */
    public string $delimiter = ',';

    /**
     * @var string the field enclosure (one character only)
     */
    public string $enclosure = '"';

    /**
     * @var string the escape character (one character only)
     */
    public string $escapeChar = "\\";

    /**
     * Formats the specified response.
     *
     * @param YiiResponse $response the response to be formatted.
     */
    public function format($response): void
    {
        if (stripos($this->contentType, 'charset') === false) {
            $this->contentType .= '; charset=' . $response->charset;
        }
        $response->getHeaders()->set('Content-Type', $this->contentType);

        $data = is_iterable($response->data) ? $response->data : [];
        if (empty($data) && empty($this->headers)) {
            $response->content = '';
            return;
        }

        $file = tempnam(sys_get_temp_dir(), 'csv');
        $fp = fopen($file, 'wb');

        // Add BOM to fix UTF-8 in Excel
        // h/t https://www.php.net/manual/en/function.fputcsv.php#118252
        fputs($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $suspectCharacters = ['=', '-', '+', '@'];

        $headersIncluded = false;

        foreach ($data as $row) {
            // Include the headers
            if (!$headersIncluded && $this->includeHeaderRow) {
                $headers = $this->headers ?? array_keys($row);
                fputcsv($fp, $headers, ',');
                $headersIncluded = true;
            }
            foreach ($row as &$field) {
                if (is_scalar($field)) {
                    $field = (string)$field;

                    // Guard against CSV injection attacks
                    // https://github.com/thephpleague/csv/issues/268
                    if ($field && $field !== '' && in_array($field[0], $suspectCharacters)) {
                        $field = "\t$field";
                    }
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
