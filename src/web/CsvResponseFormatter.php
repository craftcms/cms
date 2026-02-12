<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\BaseWriter;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use yii\web\Response as YiiResponse;

/**
 * CsvResponseFormatter formats the given data into CSV response content.
 *
 * It is used by [[YiiResponse]] to format response data.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class CsvResponseFormatter extends BaseSpreadsheetResponseFormatter
{
    /**
     * @var string the Content-Type header for the response
     */
    public string $contentType = 'text/csv';

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
     * @deprecated in 5.9.0
     */
    public string $escapeChar = "\\";

    /**
     * @inheritdoc
     */
    protected function createWriter(Spreadsheet $spreadsheet): BaseWriter
    {
        $writer = new Csv($spreadsheet);
        $writer->setDelimiter($this->delimiter);
        $writer->setEnclosure($this->enclosure);
        return $writer;
    }
}
