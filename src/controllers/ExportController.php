<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\ElementInterface;
use craft\elements\exporters\Raw;
use craft\helpers\ElementHelper;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * The ExportController class is a controller that handles exporting element data.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 * @deprecated in 3.4.4
 */
class ExportController extends Controller
{
    /**
     * @inheritdoc
     */
    public $defaultAction = 'export';

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = true;

    /**
     * Exports element data.
     *
     * @param string $elementType
     * @param string $sourceKey
     * @param array $criteria
     * @param string $exporter
     * @param string $format
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionExport(string $elementType, string $sourceKey, array $criteria, string $exporter = Raw::class, string $format = 'csv'): Response
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

        $exporter = Craft::$app->getElements()->createExporter($exporter);
        $exporter->setElementType($elementType);

        $this->response->data = $exporter->export($query);
        $this->response->format = $format;
        $this->response->setDownloadHeaders($exporter->getFilename() . ".{$format}");

        switch ($format) {
            case Response::FORMAT_JSON:
                $this->response->formatters[Response::FORMAT_JSON]['prettyPrint'] = true;
                break;
            case Response::FORMAT_XML:
                Craft::$app->language = 'en-US';
                $this->response->formatters[Response::FORMAT_XML]['rootTag'] = $elementType::pluralLowerDisplayName();
                $this->response->formatters[Response::FORMAT_XML]['itemTag'] = $elementType::lowerDisplayName();
                break;
        }

        return $this->response;
    }
}
