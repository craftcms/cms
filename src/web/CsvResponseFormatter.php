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
     * @var string[] the header row values. The unique keys across all rows in
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

        // If $this->headers is set, we can trust that the data will be uniform
        if (isset($this->headers)) {
            $headers = $this->headers;
        } else {
            // Find all the unique keys
            $keys = [];
            foreach ($data as $row) {
                // Can't use `$keys += $row` here because that wouldn't give us the desired
                // result if any numeric keys are being used
                foreach (array_keys($row) as $key) {
                    $keys[$key] = null;
                }
            }
            $headers = array_keys($keys);
            foreach ($data as &$row) {
                $normalizedRow = [];
                foreach ($headers as $key) {
                    $normalizedRow[] = $row[$key] ?? '';
                }
                $row = $normalizedRow;
            }
            unset($row);
        }

        if ($this->includeHeaderRow) {
            fputcsv($fp, $headers, ',');
        }

        foreach ($data as &$row) {
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
        unset($row);

        fclose($fp);
        $response->content = file_get_contents($file);
        unlink($file);
    }
}
