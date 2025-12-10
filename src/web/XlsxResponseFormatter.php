<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\BaseWriter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * XlsxResponseFormatter formats the given data into XLSX response content.
 *
 * It is used by [[YiiResponse]] to format response data.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.9.0
 */
class XlsxResponseFormatter extends BaseSpreadsheetResponseFormatter
{
    /**
     * @var string the Content-Type header for the response
     */
    public string $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /**
     * @inheritdoc
     */
    protected function createWriter(Spreadsheet $spreadsheet): BaseWriter
    {
        return new Xlsx($spreadsheet);
    }
}
