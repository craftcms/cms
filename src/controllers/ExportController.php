<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\ElementHelper;
use craft\helpers\FileHelper;
use craft\web\Controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * The ExportController class is a controller that handles exporting element data.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
class ExportController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $defaultAction = 'export';

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = true;

    // Public Methods
    // =========================================================================

    /**
     * Exports element data.
     *
     * @param string $elementType
     * @param string $sourceKey
     * @param array $criteria
     * @param string $format
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionExport(string $elementType, string $sourceKey, array $criteria, string $format): Response
    {
        $this->requireToken();

        /** @var string|ElementInterface $elementType */
        $query = $elementType::find();
        $source = ElementHelper::findSource($elementType, $sourceKey, 'index');

        if ($source === null) {
            throw new BadRequestHttpException('Invalid source key: ' . $sourceKey);
        }

        // Does the source specify any criteria attributes?
        if (isset($source['criteria'])) {
            Craft::configure($query, $source['criteria']);
        }

        // Override with the request's params
        if ($criteria !== null) {
            if (isset($criteria['trashed'])) {
                $criteria['trashed'] = (bool)$criteria['trashed'];
            }
            Craft::configure($query, $criteria);
        }

        /** @var array $results */
        $results = $query->asArray()->all();
        $columns = array_keys(reset($results));

        foreach ($results as &$result) {
            $result = array_values($result);
        }
        unset($result);

        // Populate the spreadsheet
        $spreadsheet = new Spreadsheet();
        if (!empty($results)) {
            $worksheet = $spreadsheet->setActiveSheetIndex(0);
            $worksheet->fromArray($columns, null, 'A1');
            $worksheet->fromArray($results, null, 'A2');
        }

        // Could use the writer factory with a $format <-> phpspreadsheet string map, but this is more simple for now.
        switch ($format) {
            case 'csv':
                $writer = new Csv($spreadsheet);
                break;
            case 'xls':
                $writer = new Xls($spreadsheet);
                break;
            case 'xlsx':
                $writer = new Xlsx($spreadsheet);
                break;
            case 'ods':
                $writer = new Ods($spreadsheet);
                break;
            default:
                throw new BadRequestHttpException('Invalid export format: ' . $format);
        }

        $file = tempnam(sys_get_temp_dir(), 'export');
        $writer->save($file);
        $contents = file_get_contents($file);
        unlink($file);

        $filename = mb_strtolower($elementType::pluralDisplayName()) . '.' . $format;
        $mimeType = FileHelper::getMimeTypeByExtension($filename);

        $response = Craft::$app->getResponse();
        $response->content = $contents;
        $response->format = Response::FORMAT_RAW;
        $response->setDownloadHeaders($filename, $mimeType);
        return $response;
    }
}
