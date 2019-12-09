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

        $results = $query->asArray()->all();
        $filename = $elementType::pluralLowerDisplayName() . '.' . $format;
        $mimeType = FileHelper::getMimeTypeByExtension($filename);

        $response = Craft::$app->getResponse();
        $response->data = $results;
        $response->format = $format;
        $response->setDownloadHeaders($filename, $mimeType);
        return $response;
    }
}
