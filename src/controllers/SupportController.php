<?php

namespace craft\controllers;

use Craft;
use craft\web\Controller;
use yii\web\Response;

/**
 * Support controller
 */
class SupportController extends Controller
{
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

    /**
     * Performs a search via the Docs service and renders a list of results.
     */
    public function actionDocsSearch(): Response
    {
        $query = Craft::$app->getRequest()->getQueryParam('search');

        if (!$query) {
            return $this->asRaw(null);
        }

        $results = Craft::$app->getDocs()->makeApiRequest('search', ['query' => $query]);

        return $this->renderTemplate('_components/widgets/Docs/results.twig', [
            'query' => $query,
            'results' => $results,
        ]);
    }
}
