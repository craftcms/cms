<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\ElementInterface;
use craft\events\ExportEvent;
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
    // Constants
    // =========================================================================

    const EVENT_MODIFY_EXPORT_QUERY = 'modifyExportQuery';
    const EVENT_MODIFY_EXPORT = 'modifyExport';

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
    public function actionExport(string $elementType, string $sourceKey, array $criteria, string $format = 'csv'): Response
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

        // Raise the modifyExport event
        $event = new ExportEvent([
            'elementType' => $elementType,
            'sourceKey' => $sourceKey,
            'criteria' => $criteria,
            'query' => $query,
        ]);
        $this->trigger(self::EVENT_MODIFY_EXPORT_QUERY, $event);

        // Get the results and add a header row to the beginning
        /** @var array $results */
        $results = $event->query->asArray()->all();
        $header = !empty($results) ? array_keys(reset($results)) : [];
        array_unshift($results, $header);

        switch ($format) {
            case 'csv':
                $file = tempnam(sys_get_temp_dir(), 'export');
                $fp = fopen($file, 'wb');
                foreach ($results as $result) {
                    fputcsv($fp, $result, ',');
                }
                fclose($fp);
                $contents = file_get_contents($file);
                unlink($file);
                break;
            default:
                throw new BadRequestHttpException('Invalid export format: ' . $format);
        }

        $filename = mb_strtolower($elementType::pluralDisplayName()) . '.' . $format;
        $mimeType = FileHelper::getMimeTypeByExtension($filename);

        // Raise the modifyExport event
        $event = new ExportEvent([
            'header' => $header,
            'results' => $results,
            'format' => $format,
            'elementType' => $elementType,
            'sourceKey' => $sourceKey,
            'criteria' => $criteria,
            'query' => $query,
            'contents' => $contents,
            'filename' => $filename,
            'mimeType' => $mimeType,
        ]);
        $this->trigger(self::EVENT_MODIFY_EXPORT, $event);

        $response = Craft::$app->getResponse();
        $response->content = $event->contents;
        $response->format = Response::FORMAT_RAW;
        $response->setDownloadHeaders($event->filename, $event->mimeType);
        return $response;
    }
}
