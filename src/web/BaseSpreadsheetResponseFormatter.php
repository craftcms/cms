<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use craft\helpers\Json;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\BaseWriter;
use yii\base\Component;
use yii\web\Response as YiiResponse;
use yii\web\ResponseFormatterInterface;

/**
 * BaseSpreadsheetResponseFormatter is the base class for response formatters for generating spreadsheet files.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.9.0
 */
abstract class BaseSpreadsheetResponseFormatter extends Component implements ResponseFormatterInterface
{
    /**
     * @var string the Content-Type header for the response
     */
    public string $contentType;

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

        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();

        if ($this->includeHeaderRow) {
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

            $activeWorksheet->fromArray($headers);
            $activeWorksheet->getStyle('1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '000000'],
                ],
            ]);
        }

        $suspectCharacters = ['=', '-', '+', '@'];

        foreach ($data as &$row) {
            foreach ($row as &$field) {
                if (is_scalar($field)) {
                    $field = (string)$field;

                    // Guard against CSV injection attacks
                    // https://github.com/thephpleague/csv/issues/268
                    if ($field && in_array($field[0], $suspectCharacters)) {
                        $field = "\t$field";
                    }
                } else {
                    $field = Json::encode($field);
                }
            }
            unset($field);
        }

        $activeWorksheet->fromArray($data, startCell: $this->includeHeaderRow ? 'A2' : 'A1');

        $file = tempnam(sys_get_temp_dir(), 'xlsx');
        $fp = fopen($file, 'wb');
        $this->createWriter($spreadsheet)->save($fp);
        fclose($fp);
        $response->content = file_get_contents($file);
        unlink($file);
    }

    /**
     * Returns the writer object that should save out the spreadsheet file.
     *
     * @param Spreadsheet $spreadsheet
     * @return BaseWriter
     */
    abstract protected function createWriter(Spreadsheet $spreadsheet): BaseWriter;
}
