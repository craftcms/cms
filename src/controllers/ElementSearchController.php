<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\ElementConditionInterface;
use craft\errors\InvalidTypeException;
use craft\helpers\Component;
use craft\helpers\Cp;
use craft\helpers\Search;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Class ElementSearchController.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.8.0
 */
class ElementSearchController extends Controller
{
    /**
     * Searches for elements.
     *
     * @return Response
     */
    public function actionSearch(): Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        /** @var class-string<ElementInterface> $elementType */
        $elementType = $this->request->getBodyParam('elementType');
        $siteId = $this->request->getBodyParam('siteId');
        $criteria = $this->request->getBodyParam('criteria');
        /** @var array{class:class-string<ElementConditionInterface>}|null $conditionConfig */
        $conditionConfig = $this->request->getBodyParam('condition');
        $excludeIds = $this->request->getBodyParam('excludeIds') ?? [];
        $search = trim($this->request->getBodyParam('search'));

        if (!Component::validateComponentClass($elementType, ElementInterface::class)) {
            $message = (new InvalidTypeException($elementType, ElementInterface::class))->getMessage();
            throw new BadRequestHttpException($message);
        }

        $query = $elementType::find()
            ->siteId($siteId)
            ->search($search)
            ->orderBy(['score' => SORT_DESC])
            ->limit(5);

        if ($criteria) {
            Craft::configure($query, Component::cleanseConfig($criteria));
        }

        if ($conditionConfig) {
            $condition = Craft::$app->getConditions()->createCondition($conditionConfig);

            if ($condition instanceof ElementCondition) {
                $referenceElementId = $this->request->getBodyParam('referenceElementId');
                if ($referenceElementId) {
                    $ownerId = $this->request->getBodyParam('referenceElementOwnerId');
                    $siteId = $this->request->getBodyParam('referenceElementSiteId');
                    $criteria = [];
                    if ($ownerId) {
                        $criteria['ownerId'] = $ownerId;
                    }
                    $condition->referenceElement = Craft::$app->getElements()->getElementById(
                        (int)$referenceElementId,
                        siteId: $siteId,
                        criteria: $criteria,
                    );
                }

                $condition->modifyQuery($query);
            }
        }

        $elements = $query->all();

        $return = [];
        $exactMatches = [];
        $excludes = [];
        $exactMatch = false;

        if (!empty($elements)) {
            $search = Search::normalizeKeywords($search);

            foreach ($elements as $element) {
                $exclude = in_array($element->id, $excludeIds, false);

                $return[] = [
                    'id' => $element->id,
                    'title' => $element->title,
                    'html' => Cp::chipHtml($element, [
                        'hyperlink' => false,
                        'class' => 'chromeless',
                    ]),
                    'exclude' => $exclude,
                ];

                $title = $element->title ?? (string)$element;
                $title = Search::normalizeKeywords($title);

                if ($title == $search) {
                    $exactMatches[] = 1;
                    $exactMatch = true;
                } else {
                    $exactMatches[] = 0;
                }

                $excludes[] = $exclude ? 1 : 0;
            }

            // prevent the default sort order from changing beyond $excludes + $exactMatches
            $range = range(1, count($return));

            array_multisort($excludes, SORT_ASC, $exactMatches, SORT_DESC, $range, $return);
        }

        return $this->asJson([
            'elements' => $return,
            'exactMatch' => $exactMatch,
        ]);
    }
}
